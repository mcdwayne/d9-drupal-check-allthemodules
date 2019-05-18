<?php

namespace Drupal\hidden_tab\Plugable\Render;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\hidden_tab\Plugable\Annotation\HiddenTabRenderAnon;
use Drupal\hidden_tab\Plugable\HiddenTabPluginManager;
use Symfony\Component\HttpFoundation\Exception\RequestExceptionInterface;

/**
 * The plugin manager.
 *
 * @see \Drupal\hidden_tab\Plugable\Render\HiddenTabRenderInterface
 */
class HiddenTabRenderPluginManager extends HiddenTabPluginManager {

  protected $pid = HiddenTabRenderInterface::PID;

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces,
                              CacheBackendInterface $cache_backend,
                              ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/HiddenTabRender',
      $namespaces,
      $module_handler,
      HiddenTabRenderInterface::class,
      HiddenTabRenderAnon::class
    );
    $this->alterInfo('hidden_tab_render_info');
    $this->setCacheBackend($cache_backend, 'hidden_tab_render_plugin');
  }

  /**
   * Facory method, create an instance from container.
   *
   * @return \Drupal\hidden_tab\Plugable\HiddenTabPluginManager
   */
  public static function instance(): HiddenTabPluginManager {
    return \Drupal::service('plugin.manager.' . HiddenTabRenderInterface::PID);
  }

}
