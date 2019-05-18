<?php

namespace Drupal\flexfield\Plugin;

/**
 * Defines an interface for Flexfield Type plugins.
 */
interface FlexFieldTypeManagerInterface {

  /**
   * Get flex field plugin items from an array of flexfield field settings.
   * @param  array $settings
   *   The array of Drupal\flexfield\Plugin\Field\FieldType\FlexItem settings.
   * @return array
   */
  public function getFlexFieldItems(array $settings);

  /**
   * Return the available widgets labels as an array keyed by plugin_id.
   * @return array
   */
  public function getFlexFieldWidgetOptions();

}
