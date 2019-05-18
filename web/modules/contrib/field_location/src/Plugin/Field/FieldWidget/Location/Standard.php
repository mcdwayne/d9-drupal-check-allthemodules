<?php

namespace Drupal\field_location\Plugin\Field\FieldWidget\Location;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Provides "location_widget_standard" field widget.
 *
 * @FieldWidget(
 *   id = "location_widget_standard",
 *   label = @Translation("Location (standard)"),
 *   field_types = {
 *     "location",
 *   },
 * )
 */
class Standard extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $default_value_widget = $this->isDefaultValueWidget($form_state);
    $definition = $items->getFieldDefinition();
    $field_name = $items->getName();
    $defaults = $definition->getDefaultValueLiteral();
    $values = $items->getValue();
    $field = $definition->getFieldStorageDefinition();

    $element += [
      '#type' => 'fieldset',
      '#title' => $field->getLabel(),
      '#attributes' => [
        'class' => ['field-location', $field_name],
      ],
    ];

    $element['preview'] = [
      '#theme' => 'google-map-preview',
    ];

    foreach ($field->getPropertyNames() as $name) {
      $property = $field->getPropertyDefinition($name);

      // Use configured value.
      if (isset($values[$delta][$name])) {
        $value = $values[$delta][$name];
      }
      // Use default if set.
      elseif (isset($defaults[$delta][$name])) {
        $value = $defaults[$delta][$name];
      }
      // Use default value from field type definition.
      else {
        $value = $property->getSetting('default_value');
      }

      $element[$name] = [
        '#type' => 'textarea',
        '#title' => $property->getLabel(),
        // Field should not be mandatory for default value setting.
        '#required' => !$default_value_widget & $property->isRequired(),
        '#default_value' => $value,
        '#attributes' => [
          'class' => ['data-container', $name],
          'readonly' => $property->isReadOnly(),
        ],
      ];

      $element['#attached']['drupalSettings']['locationFields'][$field_name][$delta][$name] = $value;
    }

    $element['#attached']['library'][] = googlemaps_library($this->getFieldSetting('googlemaps_library'));
    $element['#attached']['library'][] = 'field_location/google-maps-preview';

    return $element;
  }

}
