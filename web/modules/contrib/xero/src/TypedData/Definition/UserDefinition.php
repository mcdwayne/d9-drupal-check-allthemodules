<?php

namespace Drupal\xero\TypedData\Definition;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ComplexDataDefinitionBase;

/**
 * Xero User data definition
 */
class UserDefinition extends ComplexDataDefinitionBase implements XeroDefinitionInterface {
  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      // All properties are read-only.
      $info['UserID'] = DataDefinition::create('string')->setLabel('User Id')->setReadOnly(TRUE)->addConstraint('XeroGuidConstraint');
      $info['EmailAddress'] = DataDefinition::create('email')->setLabel('E-mail address')->setReadOnly(TRUE);
      $info['FirstName'] = DataDefinition::create('string')->setLabel('First Name')->setReadOnly(TRUE);
      $info['LastName'] = DataDefinition::create('string')->setLabel('Last Name')->setReadOnly(TRUE);
      $info['UpdatedDateUTC'] = DataDefinition::create('datetime_iso8601')->setLabel('Updated Date')->setReadOnly(TRUE);
      $info['IsSubscriber'] = DataDefinition::create('boolean')->setLabel('Subscriber?')->setReadOnly(TRUE);
      $info['OrganisationRole'] = DataDefinition::create('string')->setLabel('Organisation Role')->setReadOnly(TRUE);
    }
    return $this->propertyDefinitions;
  }
}
