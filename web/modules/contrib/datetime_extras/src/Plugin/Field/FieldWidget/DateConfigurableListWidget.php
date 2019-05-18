<?php

namespace Drupal\datetime_extras\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Plugin implementation of the 'datatime_extras_configurable_list' widget.
 *
 * @FieldWidget(
 *   id = "datatime_extras_configurable_list",
 *   label = @Translation("Configurable list (deprecated)"),
 *   field_types = {
 *     "datetime"
 *   }
 * )
 *
 * @deprecated in 1.x and will be removed before 2.0. Use
 * \Drupal\datetime_extras\Plugin\Field\FieldWidget\DateTimeDatelistNoTimeWidget
 * instead.
 */
class DateConfigurableListWidget extends DateTimeDatelistNoTimeWidget {

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    @trigger_error('The ' . __NAMESPACE__ . '\DateConfigurableListWidget is deprecated in 1.x and will be removed before 2.0. Instead, use ' . __NAMESPACE__ . '\DateTimeDatelistNoTimeWidget.', E_USER_DEPRECATED);
  }

}
