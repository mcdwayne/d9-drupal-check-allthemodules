<?php

namespace Drupal\mask\Plugin;

use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Interface for Mask's field widget plugins.
 */
interface FieldWidgetPluginInterface {

  /**
   * Returns the widget type from the plugin definition.
   *
   * @return string
   *   The widget type from the plugin definition.
   */
  public function getWidgetType();

  /**
   * Returns the widget's mask settings.
   *
   * @return array
   *   An associative array with mask settings.
   */
  public function getFieldWidgetThirdPartySettings(WidgetInterface $widget);

  /**
   * Allow modules to add settings to field widgets provided by other modules.
   *
   * @param \Drupal\Core\Field\WidgetInterface $plugin
   *   The instantiated field widget plugin.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param string $form_mode
   *   The entity form mode.
   * @param array $form
   *   The (entire) configuration form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   Returns the form array to be built.
   *
   * @see hook_field_widget_third_party_settings_form()
   */
  public function fieldWidgetThirdPartySettingsForm(WidgetInterface $plugin, FieldDefinitionInterface $field_definition, $form_mode, array $form, FormStateInterface $form_state);

  /**
   * Alters the field widget settings summary.
   *
   * @param array $summary
   *   An array of summary messages.
   * @param array $context
   *   An associative array with the following elements:
   *   - widget: The widget object.
   *   - field_definition: The field definition.
   *   - form_mode: The form mode being configured.
   *
   * @see hook_field_widget_settings_summary_alter()
   */
  public function fieldWidgetSettingsSummaryAlter(array &$summary, array $context);

  /**
   * Alter forms for field widgets provided by other modules.
   *
   * @param array $element
   *   The field widget form element as constructed by hook_field_widget_form().
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   * @param array $context
   *   An associative array containing the following key-value pairs, matching
   *   the arguments received by hook_field_widget_form():
   *   - form: The form structure to which widgets are being attached. This may
   *     be a full form structure, or a sub-element of a larger form.
   *   - field: The field structure.
   *   - instance: The field instance structure.
   *   - langcode: The language associated with $items.
   *   - items: Array of default values for this field.
   *   - delta: The order of this item in the array of subelements (0, 1, etc).
   *
   * @see hook_field_widget_form_alter()
   */
  public function fieldWidgetFormAlter(array &$element, FormStateInterface $form_state, array $context);

}
