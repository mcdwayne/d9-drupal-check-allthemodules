<?php

namespace Drupal\xero\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Xero Invoice Reminder definition
 */
class InvoiceReminderDefinition extends ComplexDataDefinitionBase implements XeroDefinitionInterface {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      $info['Enabled'] = DataDefinition::create('boolean')
        ->setLabel('Enabled?')
        ->setReadOnly(TRUE);
    }
    return $this->propertyDefinitions;
  }
}