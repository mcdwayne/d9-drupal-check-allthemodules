<?php

/**
 * @file
 * Contains \Drupal\po_translations_report\DetailsDisplayerPluginManager.
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
class DetailsDisplayerPluginManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/PoTranslationsReportDetailsDisplayer', $namespaces, $module_handler, 'Drupal\po_translations_report\DetailsDisplayerPluginInterface', 'Drupal\po_translations_report\Annotation\PoTranslationsReportDetailsDisplayer');
    $this->alterInfo('details_displayer_info');
    $this->setCacheBackend($cache_backend, 'details_displayer_info_plugins');
  }

}
