<?php

namespace Drupal\commerce_price_table\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Plugin implementation of the 'commerce_price_table' field type.
 *
 * @FieldType(
 *   id = "commerce_price_table",
 *   label = @Translation("Price table"),
 *   description = @Translation("This field stores multiple prices for products based on the quantity selected."),
 *   category = @Translation("Commerce"),
 *   default_widget = "commerce_price_table_multiple",
 *   default_formatter = "commerce_multiprice_default"
 * )
 */
class PriceTableItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field) {
    return [
      'columns' => [
        'amount' => [
          'description' => 'The price amount.',
          'type' => 'numeric',
          'precision' => 19,
          'scale' => 6,
        ],
        'currency_code' => [
          'description' => 'The currency code for the price.',
          'type' => 'varchar',
          'length' => 32,
          'not null' => TRUE,
        ],
        'min_qty' => [
          'description' => 'The minimal quantity for this amount.',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
        ],
        'max_qty' => [
          'description' => 'The maximum quantity for this amount.',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
        ],
        'data' => [
          'description' => 'A serialized array of additional price data.',
          'type' => 'text',
          'size' => 'big',
          'not null' => FALSE,
          'serialize' => TRUE,
        ],
      ],
      'indexes' => [
        'currency_price_table' => ['amount', 'currency_code'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('amount')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['amount'] = DataDefinition::create('string')
      ->setLabel(t('The price amount.'));

    $properties['currency_code'] = DataDefinition::create('string')
      ->setLabel(t('The currency code for the price.'));

    $properties['min_qty'] = DataDefinition::create('integer')
      ->setLabel(t('The minimal quantity for this amount.'))
      ->setDescription(t('Snippet code language'));

    $properties['max_qty'] = DataDefinition::create('integer')
      ->setLabel(t('The maximum quantity for this amount.'));

    $properties['data'] = DataDefinition::create('string')
      ->setLabel(t('A serialized array of additional price data.'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
        'currency_code' => [],
      ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $currencies = \Drupal::entityTypeManager()->getStorage('commerce_currency')->loadMultiple();
    $currency_codes = array_keys($currencies);

    $element = [];
    $element['currency_code'] = [
      '#type' => count($currency_codes) < 10 ? 'radios' : 'select',
      '#title' => $this->t('Available currencies'),
      '#description' => $this->t('If no currencies are selected, all currencies will be available.'),
      '#options' => array_combine($currency_codes, $currency_codes),
      '#default_value' => $this->getSetting('currency_code'),
    ];

    return $element;
  }
}
