<?php

namespace Drupal\json_ld_schema\Source;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Spatie\SchemaOrg\Type;

/**
 * Interface for JSON LD source plugins.
 */
interface JsonLdSourceInterface extends PluginInspectionInterface {

  /**
   * Get data provided by this plugin.
   *
   * @return \Spatie\SchemaOrg\Type
   *   Some schema data.
   */
  public function getData() : Type;

  /**
   * Check if the data for the plugin should appear for the current page.
   *
   * @return bool
   *   If the data returned by the plugin should appear for the current page.
   */
  public function isApplicable();

  /**
   * Get the cacheable metadata associated with the current data.
   *
   * @return \Drupal\Core\Cache\CacheableMetadata
   *   The cacheable metadata object.
   */
  public function getCacheableMetadata() : CacheableMetadata;

}
