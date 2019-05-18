<?php

namespace Drupal\record\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\record\Plugin\Field\FieldType\RecordItem;

/**
 * Plugin implementation of the 'record_item_widget' widget.
 *
 * @FieldWidget(
 *   id = "record_item_widget",
 *   module = "record",
 *   label = @Translation("Data entry widget"),
 *   field_types = {
 *     "record_item"
 *   }
 * )
 */
class RecordWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    if ($delta > 0) {
      // @todo Prevent the field from being multi-value.
      throw new \Exception("Record properties should be single value.");
      return;
    }

    $extended_schema = RecordItem::getExtendedSchema($items->getDataDefinition()->get('field_name'));
    $element['record_properties'] = [
      '#type' => 'details',
      '#title' => t('Record properties'),
      '#open' => TRUE,
    ];
    $element['record_properties']['deprecated'] = [
      '#type' => 'details',
      '#title' => t('Deprecated'),
      '#open' => FALSE,
      '#weight' => 10,
    ];
    $item = $items[$delta];

    foreach ($extended_schema['columns'] as $field => $column) {
      // @todo better fapi handling.
      if ($field != 'archived_fields') {
        $prop = [
          '#type' => 'textfield',
          '#default_value' => $item->{$field},
          '#title' => $column['record_properties']['label'],
          '#size' => 30,
          '#maxlength' => isset($column['length']) ? $column['length'] : 12,
        ];
        if (isset($column['deprecated']) &&  $column['deprecated']) {
          $element['record_properties']['deprecated'][$field] = $prop;
        }
        else {
          $element['record_properties'][$field] = $prop;
        }
      }
    }

    return $element;
  }

  /**
   * Validate.
   */
  public function validate($element, FormStateInterface $form_state) {
    // All fields here are simple text strings. Anything that requires
    // validation can be moved to full fields.
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // Hardcode 0 as the delta as this field is always singular.
    $return = [];
    foreach ($values[0]['record_properties'] as $key => $data) {
      $return[$key] = trim($data);
    }
    foreach ($values[0]['record_properties']['deprecated'] as $key => $data) {
      $return[$key] = trim($data);
    }

    return [0 => $return];
  }

}
