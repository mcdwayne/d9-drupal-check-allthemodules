<?php
namespace Drupal\pagedesigner\Service;

use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;

/**
 * HandlerPluginManager manager.
 *
 * This class provides static functions for retrieving node renderers.
 * For most use cases one needs to get the appropiate renderer using one of the static methods and process the node using the node renderer.
 *
 * @see \Drupal\Core\Archiver\Annotation\Archiver
 * @see \Drupal\Core\Archiver\ArchiverInterface
 * @see plugin_api
 */
class HandlerPluginManager extends DefaultPluginManager implements FallbackPluginManagerInterface
{
    /**
     * The drupal database connection object.
     *
     * @var Connection
     */
    protected $connection = null;

    /**
     * Temporary cache
     *
     * The nodes loaded from database during processing.
     *
     * @var Node[]
     */
    protected $revisions = [];

    /**
     *
     */
    public $currentNode = null;

    /**
     * Constructs a RendererPluginManager object.
     *
     * @param \Traversable $namespaces
     *   An object that implements \Traversable which contains the root paths
     *   keyed by the corresponding namespace to look for plugin implementations.
     * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
     *   Cache backend instance to use.
     * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
     *   The module handler to invoke the alter hook with.
     */
    public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler)
    {
        parent::__construct(
            'Plugin/pagedesigner/Handler',
            $namespaces,
            $module_handler,
            'Drupal\pagedesigner\Plugin\pagedesigner\HandlerPluginInterface',
            'Drupal\pagedesigner\Annotation\PagedesignerHandler'
        );
        $this->alterInfo('pagedesigner_handler_info');
        $this->setCacheBackend($cache_backend, 'pagedesigner_handler_info_plugins');
    }
    /**
     * Returns the latest revision of a node.
     *
     * Queries the database for the given id and language to find the newest revision id of the node.
     * The node is then loaded using loadRevision().
     *
     * @param int $id The id of the node to retrieve.
     * @param string $lang The desired language code of the node.
     * @param boolean $fresh Whether to ignore entity and temporary cache.
     * @return Node
     * @see NodeCollector::loadRevision()
     */
    public function getLatestRevision($id, $lang, $fresh = false)
    {

        if (self::$connection == null) {
            self::$connection = \Drupal::database();
        }

        $query = self::$connection->select('node_field_revision', 'nfr')
            ->fields('nfr', array('vid'));
        $query->condition('nfr.nid', $id, '=')
            ->condition('nfr.langcode', $lang, 'LIKE')
            ->orderBy('nfr.changed', 'DESC')
            ->orderBy('nfr.vid', 'DESC')
            ->range(0, 1);

        $stmt = $query->execute();
        $data = $stmt->fetchAssoc();
        $vid = null;
        if ($data && count($data) > 0) {
            $vid = $data['vid'];
        }
        $revision = self::loadRevision($id, $vid, $lang, $fresh);
        return $revision;
    }

    /**
     * Returns the latest revision of a node.
     *
     * Queries the database for the given id and language to find the newest revision id of the node.
     * The node is then loaded using loadRevision().
     *
     * @param int $id The id of the node to retrieve.
     * @param string $lang The desired language code of the node.
     * @param boolean $fresh Whether to ignore entity and temporary cache.
     * @return Node
     * @see NodeCollector::loadRevision()
     */
    public function getLatestRevisionSource($id, $fresh = false)
    {
        $node = Node::load($id);
        if ($node != null) {
            $lang = $node->language()->getId();
            $node = self::getLatestRevision($id, $lang, $fresh);
        }
        return $node;
    }

    /**
     * Loads a specific revision of a node.
     *
     * Queries the temporary, the entity cache or the databse for the given revision id of the given node in the given language.
     * If a node has to be retrieved from the database it is then added both to the temporary cache for faster processing.
     * The revision is not added to the entity cache. If desired calling cacheRevisions() will store all temporary items to the entity cache.
     *
     * @param int $id The id of the node to retrieve.
     * @param int $vid The revision id.
     * @param string $lang The desired language code of the node.
     * @param boolean $fresh Whether to ignore entity and temporary cache.
     * @return Node
     * @see NodeCollector::cacheRevisions()
     */
    public function loadRevision($id, $vid, $lang, $fresh = false)
    {
        $cid = 'iq_block_module:revision:' . $id . ':' . $lang;
        $cacheBackend = \Drupal::cache('entity');
        if (!$fresh && isset(self::$revisions[$cid])) {
            $revision = self::$revisions[$cid]['data'];
            return $revision;
        } elseif (!$fresh && $cache = $cacheBackend->get($cid)) {
            $revision = $cache->data;
            return $revision;
        } else {
            $nodeStorage = \Drupal::entityTypeManager()->getStorage('node');
            if ($vid != null) {
                $revision = $nodeStorage->loadRevision($vid);
                if ($revision != null) {
                    $revision = $revision->getTranslation($lang);
                    self::$revisions[$cid] = array('data' => $revision, 'tags' => array('node:' . $id));
                    return $revision;
                }
            }
            $node = $nodeStorage->load($id);
            if ($node != null && $node->hasTranslation($lang)) {
                return $node->getTranslation($lang);
            }
        }
        return null;
    }

    /**
     * Get a node.
     *
     * Returns the published version of a node  or the latest revision if the user may bypass node access in the given language.
     *
     * @param int $id The id of the node to retrieve.
     * @param AccountInterface $user The current user.
     * @param string $lang The desired language code of the node.
     * @return Node
     */
    public function getNode($id, AccountInterface $user, $lang)
    {
        $node = \Drupal\Node\Entity\Node::load($id);
        if ($node != null) {
            if ($user->hasPermission('bypass node access')) {
                $node = NodeCollector::getLatestRevision($node->id(), $lang);
            }
        }
        return $node;
    }

    /**
     * Cache temporary items.
     *
     * Stores all nodes/revisions retrieved during processing to the entity cache.
     *
     * @return void
     */
    public function cacheRevisions()
    {
        if (count(self::$revisions) > 0) {
            $cacheBackend = \Drupal::cache('entity');
            $cacheBackend->setMultiple(self::$revisions);
        }
    }

    /**
     * Overrides PluginManagerBase::getInstance().
     *
     * @param array $options
     *   An array with the following key/value pairs:
     *   - id: The id of the plugin.
     *   - type: The type of the pattern field.
     *
     * @return \Drupal\pagedesigner\Plugin\pagedesigner\HandlerPluginInterface[]
     *   A list of Handler objects.
     */
    public function getInstance(array $options)
    {
        $handlers = [];
        $type = '*';
        if (!empty($options['entity'])) {
            $type = $options['entity']->bundle();
        } else {
            $type = $options['type'];
        }
        $configuration = []; //'type' => $type, 'entity' => $options['entity']];
        foreach ($this->getDefinitions() as $plugin_id => $definition) {
            if (in_array($type, $definition['types'])) {
                $handlers[] = $this
                    ->createInstance($plugin_id, $configuration);
            }
        }
        if (empty($handlers)) {
            $handlers[] = $this
                ->createInstance('default', $configuration);
        }
        return $handlers;
    }

    /**
     * @return \Drupal\pagedesigner\Plugin\pagedesigner\HandlerPluginInterface[]
     *   A list of Handler objects.
     */
    public function getHandlers()
    {
        $handlers = [];
        $configuration = [];
        foreach ($this->getDefinitions() as $plugin_id => $definition) {
            $handlers[] = $this
                ->createInstance($plugin_id, $configuration);
        }
        return $handlers;
    }

    /**
     * {@inheritDoc}
     */
    public function getFallbackPluginId($plugin_id, array $configuration = array())
    {
        return 'standard';
    }
}
