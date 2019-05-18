<?php

/**
 * @file
 * Implementation of the default field_properties field widget.
 */

namespace Drupal\field_properties\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'field_properties_widget' widget.
 *
 * @FieldWidget(
 *   id = "field_properties_widget",
 *   label = @Translation("Properties Editor"),
 *
 *   field_types = {
 *     "field_properties"
 *   },
 *   settings = {
 *   }
 * )
 */
class FieldPropertiesWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $element['name'] = array(
      '#title' => t('Name'),
      '#type' => 'textfield',
      '#description' => t('The name of the property. Leave an empty name to remove this property.'),
      '#default_value' => isset($items[$delta]->name) ? $items[$delta]->name : NULL,
    );

    $definitions = \Drupal::service('plugin.field_properties.type')
      ->getSortedDefinitions();
    $field_properties_types_options = array();
    foreach ($definitions as $plugin_definition) {
      $field_properties_types_options[$plugin_definition['id']] = $plugin_definition['label'];
    }
    $default = 'string';
    $element['type'] = array(
      '#title' => t('Type'),
      '#type' => 'select',
      '#required' => TRUE,
      '#options' => $field_properties_types_options,
      '#default_value' => isset($items[$delta]->type) ? $items[$delta]->type : $default,
    );
    $element['value'] = array(
      '#title' => t('Value'),
      '#type' => 'textarea',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#rows' => 2,
    );
    return $element;
  }

}
