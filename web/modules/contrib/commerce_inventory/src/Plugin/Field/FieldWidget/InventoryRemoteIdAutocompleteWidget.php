<?php

namespace Drupal\commerce_inventory\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of Inventory Remote ID autocomplete widget.
 *
 * @FieldWidget(
 *   id = "commerce_inventory_remote_id_autocomplete",
 *   label = @Translation("Autocomplete"),
 *   description = @Translation("An autocomplete text field."),
 *   field_types = {
 *     "commerce_remote_id"
 *   },
 *   multiple_values = TRUE
 * )
 */
class InventoryRemoteIdAutocompleteWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'size' => '60',
      'placeholder' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['size'] = [
      '#type' => 'number',
      '#title' => t('Size of textfield'),
      '#default_value' => $this->getSetting('size'),
      '#min' => 1,
      '#required' => TRUE,
    ];
    $element['placeholder'] = [
      '#type' => 'textfield',
      '#title' => t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder'),
      '#description' => t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = t('Textfield size: @size', ['@size' => $this->getSetting('size')]);
    $placeholder = $this->getSetting('placeholder');
    if (!empty($placeholder)) {
      $summary[] = t('Placeholder: @placeholder', ['@placeholder' => $placeholder]);
    }
    else {
      $summary[] = t('No placeholder');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce\Plugin\Field\FieldType\RemoteIdFieldItemListInterface $items */
    $entity = $items->getEntity();

    // Append the match operation to the provider settings.
    $provider_settings = [
      'provider' => $entity->bundle(),
      'entity' => $entity,
    ];

    $element += [
      '#type' => 'commerce_inventory_remote_id',
      '#provider' => $entity->bundle(),
      '#target_type' => $this->getFieldSetting('target_type') ?: NULL,
      '#provider_settings' => $provider_settings,
      '#maxlength' => 1024,
      '#default_value' => $items->getByProvider($entity->bundle()),
      '#size' => $this->getSetting('size'),
      '#placeholder' => $this->getSetting('placeholder'),
      '#element_validate' => [[get_class($this), 'multipleValidate']],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $error, array $form, FormStateInterface $form_state) {
    return isset($element['#value']) ? $element['#value'] : FALSE;
  }

  /**
   * Element validation helper.
   *
   * @param array $element
   *   A form element array containing basic properties for the widget.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function multipleValidate(array $element, FormStateInterface $form_state) {
    $items[] = [
      'provider' => $element['#provider'],
      'value' => $element['#value'],
    ];
    $form_state->setValueForElement($element, $items);
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce\Plugin\Field\FieldType\RemoteIdFieldItemListInterface $items */
    $field_name = $this->fieldDefinition->getName();

    // Extract the values from $form_state->getValues().
    $path = array_merge($form['#parents'], [$field_name]);
    $key_exists = NULL;
    $values = NestedArray::getValue($form_state->getValues(), $path, $key_exists);

    if (!empty($values)) {
      $provider = $values[0]['provider'];
      $value = $values[0]['value'];
      $current_value = $items->getByProvider($provider);

      // Set item if value is new or changed.
      if ((is_null($current_value) && !empty($value)) || (!is_null($current_value) && $value !== $current_value)) {
        $items->setByProvider($values[0]['provider'], $values[0]['value']);
      }

    }

  }

}
