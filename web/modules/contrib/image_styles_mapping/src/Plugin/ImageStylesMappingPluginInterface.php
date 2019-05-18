<?php

namespace Drupal\image_styles_mapping\Plugin;

/**
 * Interface for a plugin that add columns on image styles mapping reports.
 *
 * @ingroup plugin_api
 */
interface ImageStylesMappingPluginInterface {

  /**
   * Get the plugin's dependencies.
   *
   * @return array
   *   The plugin's dependencies.
   */
  public function getDependencies();

  /**
   * Get the header for the column added by the plugin.
   *
   * @return string
   *   The header for the column added by the plugin.
   */
  public function getHeader();

  /**
   * Get the row for the column added by the plugin.
   *
   * @param array $field_settings
   *   The field display of the row.
   *
   * @return \Drupal\Component\Render\FormattableMarkup
   *   The row content for the plugin for this field.
   */
  public function getRowData(array $field_settings);

}
