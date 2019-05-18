<?php

namespace Drupal\price\Plugin\Field\FieldType;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\price\PriceModified;

/**
 * Plugin implementation of the 'modified price' field type.
 *
 * @FieldType(
 *   id = "price_modified",
 *   label = @Translation("Modified Price"),
 *   description = @Translation("Stores a decimal number, price modifier and a three letter currency code."),
 *   category = @Translation("Price"),
 *   default_widget = "price_modified_default",
 *   default_formatter = "price_modified_default",
 * )
 */
class PriceModifiedItem extends PriceItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['modifier'] = DataDefinition::create('string')
      ->setLabel(t('Modifier'))
      ->setRequired(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);

    $schema['columns']['modifier'] = [
      'description' => 'The currency code.',
      'type' => 'varchar',
      'length' => EntityTypeInterface::ID_MAX_LENGTH,
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'available_modifiers' => [],
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::fieldSettingsForm($form, $form_state);

    $modifiers = \Drupal::entityTypeManager()->getStorage('price_modifier')->loadMultiple();
    $modifier_codes = array_keys($modifiers);

    $modifier_options = [];
    foreach ($modifier_codes as $modifier_code) {
      $modifier_options[$modifier_code] = $modifiers[$modifier_code]->label();
    }

    $element['available_modifiers'] = [
      '#type' => count($modifier_codes) < 10 ? 'checkboxes' : 'select',
      '#title' => $this->t('Available modifiers'),
      '#description' => $this->t('If no modifiers are selected, all modifiers will be available.'),
      '#options' => $modifier_options,
      '#default_value' => $this->getSetting('available_modifiers'),
      '#multiple' => TRUE,
      '#size' => 5,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $manager = \Drupal::typedDataManager()->getValidationConstraintManager();
    $constraints = parent::getConstraints();

    $available_modifiers = $this->getSetting('available_modifiers');
    $constraints[] = $manager->create('PriceModifier', ['availableModifiers' => $available_modifiers]);

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return $this->number === NULL || $this->number === '' || empty($this->currency_code) || empty($this->modifier);
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    // Allow callers to pass a Price value object as the field item value.
    if ($values instanceof PriceModified) {
      $price = $values;
      $values = [
        'number' => $price->getNumber(),
        'currency_code' => $price->getCurrencyCode(),
        'modifier' => $price->getModifier(),
      ];
    }
    parent::setValue($values, $notify);
  }

  /**
   * Gets the Price value object for the current field item.
   *
   * @return \Drupal\price\PriceModified
   *   The Price value object.
   */
  public function toPrice() {
    return new PriceModified($this->number, $this->currency_code, $this->modifier);
  }

}
