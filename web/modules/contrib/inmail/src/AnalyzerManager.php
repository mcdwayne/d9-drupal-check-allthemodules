<?php

namespace Drupal\inmail;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Plugin manager for Inmail message handlers.
 *
 * @ingroup handler
 */
class AnalyzerManager extends DefaultPluginManager implements AnalyzerManagerInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/inmail/Analyzer', $namespaces, $module_handler, 'Drupal\inmail\Plugin\inmail\Analyzer\AnalyzerInterface', 'Drupal\inmail\Annotation\Analyzer');
  }

}
