<?php

namespace Drupal\xero\TypedData\Definition;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\Core\TypedData\ComplexDataDefinitionBase;

/**
 * Xero Contact data definition
 *
 * @todo ContactPersons, ContactGroups, and some other read-only properties.
 */
class ContactDefinition extends ComplexDataDefinitionBase implements XeroDefinitionInterface {
  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      // UUID is read only.
      $info['ContactID'] = DataDefinition::create('string')->setLabel('Contact Id')->addConstraint('XeroGuidConstraint');

      // Writeable properties.
      $info['Name'] = DataDefinition::create('string')->setRequired(TRUE)->setLabel('Name');
      $info['FirstName'] = DataDefinition::create('string')->setLabel('First Name');
      $info['LastName'] = DataDefinition::create('string')->setLabel('Last Name');
      $info['EmailAddress'] = DataDefinition::create('email')->setLabel('E-mail address');
      $info['SkypeUserName'] = DataDefinition::create('string')->setLabel('Skype name');
      $info['BankAccountDetails'] = DataDefinition::create('string')->setLabel('Bank account');
      $info['TaxNumber'] = DataDefinition::create('string')->setLabel('Tax number');
      $info['AccountsReceivableTaxType'] = DataDefinition::create('string')->setLabel('AR Tax Type');
      $info['AccountsPayableTaxType'] = DataDefinition::create('string')->setLabel('AP Tax Type');
      $info['Addresses'] = ListDataDefinition::create('xero_address')->setLabel('Addresses');
      $info['Phones'] = ListDataDefinition::create('xero_phone')->setLabel('Phones');
      $info['UpdatedDateUTC'] = DataDefinition::create('datetime_iso8601')->setLabel('Updated Date');
      $info['IsSupplier'] = DataDefinition::create('boolean')->setLabel('Is supplier?');
      $info['IsCustomer'] = DataDefinition::create('boolean')->setLabel('Is customer?');
      $info['DefaultCurrency'] = DataDefinition::create('string')->setLabel('Default currency');
      $info['XeroNetworkKey'] = DataDefinition::create('string')->setLabel('Xero Network Key');

      // Read-only.
      $info['Website'] = DataDefinition::create('uri')->setLabel('Web site')->setReadOnly(TRUE);
      $info['BrandingTheme'] = DataDefinition::create('string')->setLabel('Branding theme')->setReadOnly(TRUE);
    }
    return $this->propertyDefinitions;
  }
}
