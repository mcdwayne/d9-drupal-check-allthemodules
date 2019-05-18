<?php
namespace Drupal\pagetree\Service;

use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\node\Entity\Node;
use Drupal\pagetree\Plugin\pagetree\StatePluginInterface;

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
class StatePluginManager extends DefaultPluginManager implements FallbackPluginManagerInterface
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
            'Plugin/pagetree/State',
            $namespaces,
            $module_handler,
            'Drupal\pagetree\Plugin\pagetree\StatePluginInterface',
            'Drupal\pagetree\Annotation\StateHandler'
        );
        $this->alterInfo('pagetree_state_info');
        $this->setCacheBackend($cache_backend, 'pagetree_state_info_plugins');
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
            function (StatePluginInterface $a, StatePluginInterface $b) {
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
