<?php

namespace Drupal\map_widget\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'map_item_widget' widget.
 *
 * @author Shawn P. Duncan <code@sd.shawnduncan.org>
 *
 * Copyright 2019 by Shawn P. Duncan.  This code is
 * released under the GNU General Public License.
 * Which means that it is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or (at
 * your option) any later version.
 * http://www.gnu.org/licenses/gpl.html
 *
 * @FieldWidget(
 *   id = "map_assoc_widget",
 *   label = @Translation("Associative Array"),
 *   field_types = {
 *     "map"
 *   }
 * )
 */
class AssociativeArrayWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();
    $settings['size'] = 60;
    $settings['key_placeholder'] = '';
    $settings['value_placeholder'] = '';
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['size'] = [
      '#type' => 'number',
      '#title' => t('Size of key and value input elements'),
      '#default_value' => $this->getSetting('size'),
      '#required' => TRUE,
      '#min' => 1,
    ];
    $elements['key_placeholder'] = [
      '#type' => 'textfield',
      '#title' => t('Placeholder for the key form field'),
      '#default_value' => $this->getSetting('key_placeholder'),
    ];
    $elements['value_placeholder'] = [
      '#type' => 'textfield',
      '#title' => t('Placeholder for the value form field'),
      '#default_value' => $this->getSetting('value_placeholder'),
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = t(
      'Key & value input size: @size',
      ['@size' => $this->getSetting('size')]
    );
    if (!empty($this->getSetting('key_placeholder'))) {
      $summary[] = t(
        'Key placeholder: @placeholder',
        ['@placeholder' => $this->getSetting('key_placeholder')]
      );
    }
    if (!empty($this->getSetting('value_placeholder'))) {
      $summary[] = t(
        'Value placeholder: @placeholder',
        ['@placeholder' => $this->getSetting('value_placeholder')]
      );
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(
    FieldItemListInterface $items,
    $delta,
    array $element,
    array &$form,
    FormStateInterface $form_state
  ) {
    $required = isset($element['#required']) ? $element['#required'] : FALSE;
    $field_name = $this->fieldDefinition->getName();
    $element['#field_name'] = $field_name;
    $notMultiple = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality() == 1;
    $value = $items->isEmpty() ? [] : $items[$delta]->value;
    $count = $this->initCount($element, $delta, $value, $form_state);
    if ($notMultiple) {
      $element['#theme_wrappers'] = ['form_element'];
    }
    $element['value'] = $this->valueForm($value, $count, $required);
    // Add 'add more' button, if not working with a programmed form.
    if (!$form_state->isProgrammed()) {
      $parents = isset($element['#parents']) ? $element['#parents'] : [];
      $field_name = $items->getName();
      $id_prefix = implode('-', array_merge($parents, [$field_name], [$delta]));
      $wrapper_id = Html::getUniqueId($id_prefix . '-map-assoc-more-wrapper');
      $element['#prefix'] = '<div id="' . $wrapper_id . '">';
      $element['#suffix'] = '</div>';

      $element['add_more'] = [
        '#type' => 'submit',
        '#name' => strtr($id_prefix, '-', '_') . '_add_more',
        '#value' => t('Add an entry'),
        '#attributes' => ['class' => ['field-add-more-submit']],
        '#limit_validation_errors' => [array_merge($parents, [$field_name])],
        '#submit' => [[get_class($this), 'addMorePairsSubmit']],
        '#ajax' => [
          'callback' => [get_class($this), 'addMorePairsAjax'],
          'wrapper' => $wrapper_id,
          'effect' => 'fade',
        ],
      ];
    }
    return $element;
  }

  /**
   * Helper function to build the value form element array.
   *
   * @param array $value
   *   The value.
   * @param int $count
   *   The number of array elements.
   * @param bool $required
   *   Is the value required?
   *
   * @return array
   *   The render array.
   */
  protected function valueForm(array $value, $count, $required) {
    return [
      '#type' => 'map_associative',
      '#default_value' => $value,
      '#key_placeholder' => $this->getSetting('key_placeholder'),
      '#value_placeholder' => $this->getSetting('value_placeholder'),
      '#size' => $this->getSetting('size'),
      '#count' => $count,
      '#required' => $required,
    ];
  }

  /**
   * Submission handler for the "Add another item" button.
   */
  public static function addMorePairsSubmit(
    array $form,
    FormStateInterface $form_state
  ) {
    $button = $form_state->getTriggeringElement();
    // Go one level up in the form, and get this delta value.
    $element = NestedArray::getValue(
      $form,
      array_slice($button['#array_parents'], 0, -1)
    );
    $field_name = $element['#field_name'];
    $parents = $element['#field_parents'];
    $delta = $element['#delta'];
    // Increment the associative item count.
    $field_state = static::getWidgetState($parents, $field_name, $form_state);
    $field_state['map_assoc_count'][$delta]++;
    static::setWidgetState($parents, $field_name, $form_state, $field_state);
    $form_state->setRebuild();
  }

  /**
   * Ajax callback for the "Add another item" button.
   *
   * This returns the new page content to replace the page content made obsolete
   * by the form submission.
   */
  public static function addMorePairsAjax(
    array $form,
    FormStateInterface $form_state
  ) {
    $button = $form_state->getTriggeringElement();

    // Go one level up in the form, and get this delta value.
    $element = NestedArray::getValue(
      $form,
      array_slice($button['#array_parents'], 0, -1)
    );
    // Add a DIV around this element.
    $element['#prefix'] = '<div class="ajax-new-content">' . (isset($element['#prefix']) ? $element['#prefix'] : '');
    $element['#suffix'] = (isset($element['#suffix']) ? $element['#suffix'] : '') . '</div>';

    return $element;
  }

  /**
   * Include the element count for this item in the field state.
   *
   * @param array $element
   *   The current field element.
   * @param int $delta
   *   The item delta in the field.
   * @param array $value
   *   The value of the item.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return int
   *   The current delta count.
   */
  protected function initCount(
    array $element,
    $delta,
    array $value,
    FormStateInterface $form_state
  ) {
    $field_name = $element['#field_name'];
    $parents = $element['#field_parents'];

    // Set the array element count for this delta if not set.
    $field_state = static::getWidgetState($parents, $field_name, $form_state);
    if (!isset($field_state['map_assoc_count'])) {
      $field_state['map_assoc_count'] = [];
    }
    if (!isset($field_state['map_assoc_count'][$delta])) {
      $count = count($value);
      $field_state['map_assoc_count'][$delta] = $count ? $count : 1;
    }
    static::setWidgetState($parents, $field_name, $form_state, $field_state);

    return $field_state['map_assoc_count'][$delta];
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(
    array $values,
    array $form,
    FormStateInterface $form_state
  ) {
    foreach ($values as $delta => &$value) {
      // The original input array is being merged along side the output
      // of AssociativeArray::valueCallback.
      if (isset($value['value'][$delta])) {
        unset($value['value'][$delta]);
      }
    }
    return parent::massageFormValues(
      $values,
      $form,
      $form_state
    );
  }

}
