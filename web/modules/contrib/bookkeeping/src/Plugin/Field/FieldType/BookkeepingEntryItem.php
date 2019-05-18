<?php

namespace Drupal\bookkeeping\Plugin\Field\FieldType;

use Drupal\commerce_price\Price;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'bookkeeping_entry' field type.
 *
 * @property string target_id
 * @property string amount
 * @property string currency_code
 * @property int type
 *
 * @FieldType(
 *   id = "bookkeeping_entry",
 *   label = @Translation("Bookkeeping Entry"),
 *   category = @Translation("General"),
 *   default_formatter = "bookkeeping_entry_table"
 * )
 */
class BookkeepingEntryItem extends EntityReferenceItem {

  /**
   * Type: Debit.
   */
  const TYPE_DEBIT = 0;

  /**
   * Type: Credit.
   */
  const TYPE_CREDIT = 1;

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'target_type' => 'bookkeeping_account',
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    if ($this->amount !== NULL && $this->amount !== '') {
      return FALSE;
    }
    if (!empty($this->currency_code)) {
      return FALSE;
    }
    if ($this->type !== NULL) {
      return FALSE;
    }
    return parent::isEmpty();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['amount'] = DataDefinition::create('string')
      ->setLabel(t('Amount'))
      ->setRequired(FALSE);

    $properties['currency_code'] = DataDefinition::create('string')
      ->setLabel(t('Currency code'))
      ->setRequired(FALSE);

    $properties['type'] = DataDefinition::create('integer')
      ->setLabel(t('Type'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();

    $manager = \Drupal::typedDataManager()->getValidationConstraintManager();
    $constraints[] = $manager->create('ComplexData', [
      'amount' => [
        'Regex' => [
          'pattern' => '/^[+-]?((\d+(\.\d*)?)|(\.\d+))$/i',
        ],
      ],
    ]);

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);

    $schema['columns']['amount'] = [
      'description' => 'The number.',
      'type' => 'numeric',
      'precision' => 19,
      'scale' => 6,
    ];
    $schema['columns']['currency_code'] = [
      'description' => 'The currency code.',
      'type' => 'varchar',
      'length' => 3,
    ];
    $schema['columns']['type'] = [
      'description' => 'The transaction type.',
      'type' => 'int',
      'size' => 'tiny',
    ];

    $schema['indexes']['currency_code'] = ['currency_code'];
    $schema['indexes']['type'] = ['type'];

    return $schema;
  }

  /**
   * Get the amount and currency as a price object.
   *
   * @param int|null $type
   *   Optionally request it as a specific type. If the types doesn't match the
   *   requested type, the amount will be negated.
   *
   * @return \Drupal\commerce_price\Price
   *   The price object.
   */
  public function getPrice(int $type = NULL) {
    $multiplier = ($type === NULL || $this->type == $type) ? 1 : -1;
    return new Price($multiplier * $this->amount, $this->currency_code);
  }

}
