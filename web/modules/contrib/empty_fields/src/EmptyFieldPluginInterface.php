<?php

namespace Drupal\empty_fields;

/**
 * Defines an interface for empty fields.
 *
 * @see \Drupal\empty_fields\Annotation\EmptyField
 * @see \Drupal\empty_fields\EmptyFieldPluginBase
 * @see \Drupal\empty_fields\EmptyFieldsPluginManager
 * @see plugin_api
 */
interface EmptyFieldPluginInterface {

  /**
   * Returns id of the field.
   *
   * @return string
   *   The id of the plugin.
   */
  public function id();

  /**
   * Returns label of the field.
   *
   * @return string
   *   The label of the field.
   */
  public function getLabel();

  /**
   * Provides default values that map to FAPI elements in self::form().
   *
   * @return array
   *   Keyed array of element values.
   */
  public function defaults();

  /**
   * Provide FAPI elements to configure the empty field rendering.
   *
   * @param array $context
   *   An associative array containing:
   *   - entity: The entity being rendered.
   *   - view_mode: The view mode; for example, 'full' or 'teaser'.
   *   - display: The EntityDisplay holding the display options.
   *
   * @return array
   *   A FAPI array to be used in configuration of this empty text plugin.
   */
  public function form($context);

  /**
   * Provide the summary text to display on the field display settings page.
   *
   * @return string
   *   Text for the field formatter settings summary.
   */
  public function summaryText();

  /**
   * Used for returning values by key.
   *
   * @param array $context
   *   An associative array containing:
   *   - entity: The entity being rendered.
   *   - view_mode: The view mode; for example, 'full' or 'teaser'.
   *   - display: The EntityDisplay holding the display options.
   *
   * @var array
   *   Renderable array to display.
   */
  public function react($context);

}
