<?php

/**
 * @file
 * Contains \Drupal\po_translations_report\DisplayerPluginManager.
 */

namespace Drupal\po_translations_report;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Base class for text extractor plugin managers.
 *
 * @ingroup plugin_api
 */
class DisplayerPluginManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/PoTranslationsReportDisplayer', $namespaces, $module_handler, 'Drupal\po_translations_report\DisplayerPluginInterface', 'Drupal\po_translations_report\Annotation\PoTranslationsReportDisplayer');
    $this->alterInfo('displayer_info');
    $this->setCacheBackend($cache_backend, 'displayer_info_plugins');
  }

}
