<?php

namespace Drupal\xero\TypedData\Definition;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\xero\TaxTypeTrait;

/**
 * Xero Line Item data definition.
 */
class LineItemDefinition extends ComplexDataDefinitionBase implements XeroDefinitionInterface {

  use TaxTypeTrait;

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $tax_types = ['choices' => self::getTaxTypes()];

      $info = &$this->propertyDefinitions;
      $info['LineItemID'] = DataDefinition::create('string')
        ->setLabel('Line Item Identifier')
        ->addConstraint('XeroGuidConstraint');
      $info['Description'] = DataDefinition::create('string')
        ->setRequired(TRUE)
        ->setLabel('Description');
      $info['Quantity'] = DataDefinition::create('float')->setLabel('Quantity');
      $info['UnitAmount'] = DataDefinition::create('float')->setLabel('Unit amount');
      $info['ItemCode'] = DataDefinition::create('string')->setLabel('Item code')->setDescription('User-defined item code');
      $info['AccountCode'] = DataDefinition::create('string')->setLabel('Account code')->setDescription('User-defined account code');
      $info['Tracking'] = ListDataDefinition::create('xero_tracking_option')
        ->setLabel('Tracking categories')
        ->setDescription('User-defined tracking categories (0, 1, or 2 per line item)')
        ->addConstraint('Count', ['min' => 0, 'max' => 2]);
      $info['TaxType'] = DataDefinition::create('string')
        ->setLabel('Tax Type')
        ->addConstraint('XeroChoiceConstraint', $tax_types);
      $info['TaxAmount'] = DataDefinition::create('float')
        ->setLabel('Tax Amount');
      $info['LineAmount'] = DataDefinition::create('float')
        ->setLabel('Line Amount');
      $info['DiscountRate'] = DataDefinition::create('integer')
        ->setLabel('Discount Rate');
    }
    return $this->propertyDefinitions;
  }

}
