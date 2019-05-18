<?php

namespace Drupal\xero\TypedData\Definition;

use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ComplexDataDefinitionBase;

/**
 * Xero Journal data definition
 */
class JournalDefinition extends ComplexDataDefinitionBase implements XeroDefinitionInterface {
  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      // All properties are read-only.
      $info['JournalID'] = DataDefinition::create('string')->setLabel('Journal ID')->setReadOnly(TRUE)->addConstraint('XeroGuidConstraint');
      $info['JournalDate'] = DataDefinition::create('string')->setLabel('Date')->setReadOnly(TRUE);
      $info['JournalNumber'] = DataDefinition::create('string')->setLabel('Journal #')->setReadOnly(TRUE);
      $info['CreatedDateUTC'] = DataDefinition::create('datetime_iso8601')->setLabel('Created Date')->setReadOnly(TRUE);
      $info['Reference'] = DataDefinition::create('string')->setLabel('Reference')->setReadOnly(TRUE);
      $info['JournalLines'] = ListDataDefinition::create('xero_journal_line')->setLabel('Journal Lines')->setReadOnly(TRUE);
    }
    return $this->propertyDefinitions;
  }
}
