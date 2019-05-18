<?php

namespace Drupal\ingredient\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ingredient\IngredientUnitTrait;

/**
 * Plugin implementation of the 'ingredient_autocomplete' widget.
 *
 * @FieldWidget(
 *   id = "ingredient_autocomplete",
 *   module = "ingredient",
 *   label = @Translation("Autocomplete ingredient widget"),
 *   field_types = {
 *     "ingredient"
 *   }
 * )
 */
class IngredientWidget extends WidgetBase {

  use IngredientUnitTrait;

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $referenced_entities = $items->referencedEntities();

    // Get the enabled units and sort them for the select options.
    $units = $this->getConfiguredUnits($this->getFieldSetting('unit_sets'));
    $units = $this->sortUnitsByName($units);

    // Strange, but html_entity_decode() doesn't handle &frasl;.
    $quantity = isset($items[$delta]->quantity) ? preg_replace('/\&frasl;/', '/', ingredient_quantity_from_decimal($items[$delta]->quantity, '{%d} %d&frasl;%d', TRUE)) : '';
    $element['quantity'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Quantity'),
      '#default_value' => $quantity,
      '#size' => 8,
      '#maxlength' => 8,
      '#attributes' => ['class' => ['recipe-ingredient-quantity']],
    ];
    $element['unit_key'] = [
      '#type' => 'select',
      '#title' => $this->t('Unit'),
      '#default_value' => isset($items[$delta]->unit_key) ? $items[$delta]->unit_key : $this->getFieldSetting('default_unit'),
      '#options' => $this->createUnitSelectOptions($units),
      '#attributes' => ['class' => ['recipe-ingredient-unit-key']],
    ];
    $element['target_id'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Name'),
      '#target_type' => 'ingredient',
      '#autocreate' => [
        'bundle' => 'ingredient',
      ],
      // Entity reference field items are handling validation themselves via
      // the 'ValidReference' constraint.
      '#validate_reference' => FALSE,
      '#default_value' => isset($referenced_entities[$delta]) ? $referenced_entities[$delta] : NULL,
      '#size' => 25,
      '#maxlength' => 128,
      '#attributes' => ['class' => ['recipe-ingredient-name']],
    ];
    $element['note'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Note'),
      '#default_value' => isset($items[$delta]->note) ? $items[$delta]->note : '',
      '#size' => 40,
      '#maxlength' => 255,
      '#attributes' => ['class' => ['recipe-ingredient-note']],
    ];
    $element['#element_validate'] = [[$this, 'validate']];
    $element['#attached']['library'][] = 'ingredient/drupal.ingredient';

    return $element;
  }

  /**
   * Validate the ingredient field.
   *
   * @param array $element
   *   The ingredient field's form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validate(array $element, FormStateInterface $form_state) {
    if (empty($element['unit_key']['#value']) && !empty($element['name']['#value'])) {
      $form_state->setError($element['unit_key'], $this->t('You must choose a valid unit.'));
      return;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $key => $value) {
      // Convert fractional quantities to decimal.
      $values[$key]['quantity'] = round(ingredient_quantity_from_fraction($value['quantity']), 6);

      // The entity_autocomplete form element returns an array when an entity
      // was "autocreated", so we need to move it up a level.
      if (is_array($value['target_id'])) {
        unset($values[$key]['target_id']);
        $values[$key] += $value['target_id'];
      }
    }

    return $values;
  }

}
