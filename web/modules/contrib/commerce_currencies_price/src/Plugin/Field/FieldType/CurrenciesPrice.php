<?php

namespace Drupal\commerce_currencies_price\Plugin\Field\FieldType;

use Drupal\commerce_price\Price;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Plugin implementation of the 'commerce_currencies_price' field type.
 *
 * @FieldType(
 *   id = "commerce_currencies_price",
 *   label = @Translation("Price currencies"),
 *   description = @Translation("Field containing price field for each of enabled currencies"),
 *   category = @Translation("Commerce"),
 *   default_widget = "commerce_currencies_price_default",
 *   default_formatter = "commerce_currencies_price_formatter"
 * )
 */
class CurrenciesPrice extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'prices' => [
          'type' => 'blob',
          'size' => 'big',
          'not null' => FALSE,
          'serialize' => TRUE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['prices'] = MapDataDefinition::create()
      ->setLabel(t('Prices'));
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() {
    // The default implementation of toArray() only returns known properties.
    // For a map, return everything as the properties are not pre-defined.
    return $this->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    $this->values = [];
    if (!isset($values)) {
      return;
    }

    if (!is_array($values)) {
      $values = unserialize($values, ['allowed_classes' => FALSE]);
      if (empty($values['prices'])) {
        $values = NULL;
      }
    }

    $this->values = $values;

    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function __get($name) {
    if (!isset($this->values[$name])) {
      $this->values[$name] = [];
    }

    return $this->values[$name];
  }

  /**
   * {@inheritdoc}
   */
  public function __set($name, $value) {
    if (isset($value)) {
      $this->values[$name] = $value;
    }
    else {
      unset($this->values[$name]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    // A map item has no main property.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $isEmpty = empty($this->values['prices']);

    if (!$isEmpty) {
      // We are making check for first key - on empty submit array is still
      // there but with empty string as key.
      $isEmpty = empty(key($this->values['prices']));
    }

    return $isEmpty;
  }

  /**
   * Gets the Price value object for the current field item.
   *
   * @return \Drupal\commerce_price\Price[]
   *   List of Price value objects.
   */
  public function toPrices() {
    $prices = [];
    $data = $this->prices;

    foreach ($data as $key => $item) {
      // Skip empty fields.
      if (isset($item['number']) && $item['number'] !== '') {
        $prices[$key] = new Price((string) $item['number'], $item['currency_code']);
      }
    }

    return $prices;
  }

}
