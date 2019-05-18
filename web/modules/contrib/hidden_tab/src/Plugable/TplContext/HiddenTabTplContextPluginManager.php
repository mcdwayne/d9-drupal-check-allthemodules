<?php

namespace Drupal\hidden_tab\Plugable\TplContext;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\hidden_tab\Plugable\Annotation\HiddenTabTplContextAnon;
use Drupal\hidden_tab\Plugable\HiddenTabPluginManager;

/**
 * HiddenTabTplContextInterface plugin manager.
 *
 * @see \Drupal\hidden_tab\Plugable\TplContext\HiddenTabTplContextInterface
 */
class HiddenTabTplContextPluginManager extends HiddenTabPluginManager {

  protected $pid = HiddenTabTplContextInterface::PID;

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces,
                              CacheBackendInterface $cache_backend,
                              ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/HiddenTabTplContext',
      $namespaces,
      $module_handler,
      HiddenTabTplContextInterface::class,
      HiddenTabTplContextAnon::class
    );
    $this->alterInfo('hidden_tab_tpl_context_info');
    $this->setCacheBackend($cache_backend, 'hidden_tab_tpl_context_plugin');
  }

  /**
   * Facory method, create an instance from container.
   *
   * @return \Drupal\hidden_tab\Plugable\HiddenTabPluginManager
   */
  public static function instance(): HiddenTabPluginManager {
    return \Drupal::service('plugin.manager.' . HiddenTabTplContextInterface::PID);
  }

}
