<?php

namespace Drupal\telephone_type\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\telephone\Plugin\Field\FieldWidget\TelephoneDefaultWidget;

/**
 * Plugin implementation of the 'telephone_type_default' widget.
 *
 * @FieldWidget(
 *   id = "telephone_type_default",
 *   label = @Translation("Telephone number w/type"),
 *   field_types = {
 *     "telephone_type"
 *   }
 * )
 */
class TelephoneTypeDefaultWidget extends TelephoneDefaultWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();

    $settings['placeholder'] = '480 555 1212';
    $settings['type_required'] = FALSE;
    $settings['types'] = ['home', 'work', 'cell', 'fax'];
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $element['types'] = [
      '#type' => 'select',
      '#title' => t('Types'),
      '#options' => telephone_types_options(),
      '#default_value' => $this->getSetting('types'),
      '#multiple' => TRUE,
      '#size' => count(telephone_types_options()),
      '#description' => t('Select the types to display in form.'),
    ];
    $element['type_required'] = [
      '#type' => 'checkbox',
      '#title' => t('Type required'),
      '#default_value' => $this->getSetting('type_required'),
      '#description' => t('Check this to require a type be selected.'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    // Types.
    $types = $this->getSetting('types');
    if (!empty($types)) {
      $list = [];
      foreach ($types as $type) {
        $list[] = telephone_types_options($type);
      }

      $summary[] = t('Types: @types', ['@types' => implode(', ', $list)]);
    }
    else {
      $summary[] = t('No types');
    }

    // Required.
    $required = $this->getSetting('type_required');
    if ($required) {
      $summary[] = t('Type value is required.');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $options = $this->getSetting('types');
    $options_list = [];
    foreach ($options as $option) {
      $options_list[$option] = telephone_types_options($option);
    }

    $widget = parent::formElement($items, $delta, $element, $form, $form_state);
    $widget['type'] = [
      '#type' => 'select',
      '#title' => t('Type'),
      '#description' => t('Select the type of this telephone number.'),
      '#options' => $options_list,
      '#default_value' => isset($items[$delta]) ? $items[$delta]->type : NULL,
      '#required' => $this->getSetting('type_required'),
      '#empty_value' => '',
      '#maxlength' => 25,
      '#weight' => '25',
    ];

    // Change display of number to National format.
    if (!empty($widget['value']['#default_value'])) {
      $validator = \Drupal::service('telephone_type.validator');
      $widget['value']['#default_value'] = $validator->format($widget['value']['#default_value']);
    }

    return $widget;
  }

}
