<?php

namespace Drupal\xero\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 *
 * @FieldType(
 *   id = "xero_reference",
 *   label = @Translation("Xero reference"),
 *   category = @Translation("Reference"),
 *   description = @Translation("A reference to a Xero data type such as a Contact, Account, BankTransaction, etc... stored as a globally-unique identifier."),
 *   no_ui = FALSE,
 *   default_widget = "xero_textfield",
 *   default_formatter = "xero_reference"
 * )
 */
class XeroReference extends FieldItemBase {

  /**
   * Get available xero types.
   *
   * @todo use dependency injection and get xero types from plugin manager (?).
   *
   * @return array
   *   An indexed array of xero data type plugin ids.
   */
  public static function getTypes() {
    return array(
      'xero_account', 'xero_bank_transaction', 'xero_contact', 'xero_customer',
      'xero_credit_note', 'xero_employee', 'xero_expense', 'xero_invoice',
      'xero_journal', 'xero_payment', 'xero_receipt', 'xero_user',
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = array();

    $properties['guid'] = DataDefinition::create('string')->setLabel(t('GUID'))->addConstraint('XeroGuidConstraint');
    $properties['label'] = DataDefinition::create('string')->setLabel(t('Label'));
    $properties['type'] = DataDefinition::create('string')->setLabel(t('Type'))->addConstraint('Choice', self::getTypes());

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'guid';
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'guid' => array('type' => 'varchar', 'length' => 36, 'not null' => TRUE),
        'label' => array('type' => 'varchar', 'length' => 255, 'not null' => FALSE),
        'type' => array('type' => 'varchar', 'length' => 100, 'not null' => TRUE),
      ),
      'indexes' => array(
        'type' => array('type'),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return !isset($this->guid) || $this->guid === NULL || empty($this->guid);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    $guid = preg_replace('/[{}]/', '', $this->guid);
    $this->set('guid', $guid, TRUE);
  }

}
