<?php

namespace Drupal\twig_extender\Plugin\Twig;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\YamlDiscoveryDecorator;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;

/**
 * Plugin type manager for all twig plugins.
 */
class TwigExtensionsPluginManager extends DefaultPluginManager implements TwigPluginManagerInterface {

  /**
   * Constructs a TwigExtensionsPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   *   The theme handle to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces,
                              CacheBackendInterface $cacheBackend,
                              ModuleHandlerInterface $moduleHandler,
                              ThemeHandlerInterface $themeHandler) {
    $pluginInterface = 'Drupal\twig_extender\Plugin\Twig\TwigExtensionInterface';
    $pluginAnnotation = 'Drupal\twig_extender\Annotation\TwigPlugin';
    parent::__construct("Plugin/TwigPlugin", $namespaces, $moduleHandler, $pluginInterface, $pluginAnnotation);
    $discovery = $this->getDiscovery();
    $this->discovery = new YamlDiscoveryDecorator(
      $discovery,
      'twigplugins',
      $moduleHandler->getModuleDirectories() + $themeHandler->getThemeDirectories()
    );
    $this->themeHandler = $themeHandler;
    $this->moduleHandler = $moduleHandler;
    $this->setCacheBackend($cacheBackend, 'twig_extender');
    $this->defaults += [
      'class' => 'Drupal\twig_extender\Plugin\Twig\TwigPluginBase',
    ];
    $this->alterInfo('twig_extender');
  }

  /**
   * {@inheritdoc}
   */
  protected function providerExists($provider) {
    return $this->moduleHandler->moduleExists($provider) || $this->themeHandler->themeExists($provider);
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $pluginId) {
    parent::processDefinition($definition, $pluginId);

    // Add the module or theme path to the 'path'.
    if ($this->moduleHandler->moduleExists($definition['provider'])) {
      $definition['provider_type'] = 'module';
      return;
    }
    elseif ($this->themeHandler->themeExists($definition['provider'])) {
      $definition['provider_type'] = 'theme';
      return;
    }
  }

}
