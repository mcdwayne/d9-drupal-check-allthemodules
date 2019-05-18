<?php
namespace Drupal\pagedesigner\Service;

use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * AssetPluginManager manager.
 *
 * @see plugin_api
 */
class AssetPluginManager extends DefaultPluginManager implements FallbackPluginManagerInterface
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
            'Plugin/pagedesigner/Asset',
            $namespaces,
            $module_handler,
            'Drupal\pagedesigner\Plugin\pagedesigner\AssetPluginInterface',
            'Drupal\pagedesigner\Annotation\PagedesignerAsset'
        );
        $this->alterInfo('pagedesigner_asset_info');
        $this->setCacheBackend($cache_backend, 'pagedesigner_asset_info_plugins');
    }

    /**
     * Overrides PluginManagerBase::getInstance().
     *
     * @param array $options
     *   An array with the following key/value pairs:
     *   - id: The id of the plugin.
     *   - type: The type of the pattern field.
     *
     * @return \Drupal\pagedesigner\Plugin\pagedesigner\AssetPluginInterface
     *   A list of Render objects.
     */
    public function getInstance(array $options)
    {
        $processor = null;
        $type = empty($options['type']) ? '' : $options['type'];
        foreach ($this->getDefinitions() as $plugin_id => $definition) {
            if (in_array($type, $definition['types'])) {
                $processor = $this
                    ->createInstance($plugin_id);
            }
        }
        if ($processor == null) {
            $processor = $this
                ->createInstance('standard');
        }
        return $processor;
    }

    /**
     * {@inheritDoc}
     */
    public function getFallbackPluginId($plugin_id, array $configuration = array())
    {
        return 'standard';
    }
}
