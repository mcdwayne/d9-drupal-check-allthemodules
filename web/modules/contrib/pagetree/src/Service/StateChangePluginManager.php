<?php
namespace Drupal\pagetree\Service;

use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\node\Entity\Node;
use Drupal\pagetree\Plugin\pagetree\StateChangePluginInterface;

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
class StateChangePluginManager extends DefaultPluginManager implements FallbackPluginManagerInterface
{
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
            'Plugin/pagetree/StateChange',
            $namespaces,
            $module_handler,
            'Drupal\pagetree\Plugin\pagetree\StateChangePluginInterface',
            'Drupal\pagetree\Annotation\StateChangeHandler'
        );
        $this->alterInfo('pagetree_statechange_info');
        $this->setCacheBackend($cache_backend, 'pagetree_statechange_info_plugins');
    }

    /**
     * Overrides PluginManagerBase::getInstance().
     *
     * @param array $options
     *   An array with the following key/value pairs:
     *   - id: The id of the plugin.
     *   - type: The type of the pattern field.
     *
     * @return \Drupal\pagetree\Plugin\pagetree\StateChangePluginInterface[]
     *   A list of Handler objects.
     */
    public function getInstance(array $options)
    {
        $processors = [];
        $type = '*';
        if (!empty($options['entity'])) {
            $type = $options['entity']->bundle();
        } else {
            $type = $options['type'];
        }
        $configuration = []; //'type' => $type, 'entity' => $options['entity']];
        foreach ($this->getDefinitions() as $plugin_id => $definition) {
            if (in_array($type, $definition['types'])) {
                $processors[] = $this
                    ->createInstance($plugin_id, $configuration);
            }
        }
        if (empty($processors)) {
            $processors[] = $this
                ->createInstance('default', $configuration);
        }
        return $processors;
    }

    /**
     * Undocumented function
     *
     * @param array $options
     * @return void
     */
    public function getHandlers()
    {
        $handlers = [];
        $configuration = [];
        foreach ($this->getDefinitions() as $plugin_id => $definition) {
            $handlers[] = $this
                ->createInstance($plugin_id, $configuration);
        }
        usort(
            $handlers,
            function (StateChangePluginInterface $a, StateChangePluginInterface $b) {
                return $a->getPluginDefinition()['weight'] - $b->getPluginDefinition()['weight'];
            }
        );

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
