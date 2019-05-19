<?php

namespace Drupal\xero\TypedData\Definition;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ComplexDataDefinitionBase;

/**
 * Link data definition.
 */
class LinkDefinition extends ComplexDataDefinitionBase implements XeroDefinitionInterface {
  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;
      $info['Url'] = DataDefinition::create('uri')->setLabel('Url')->setRequired(TRUE);
      $info['Description'] = DataDefinition::create('string')->setLabel('Description');
    }
    return $this->propertyDefinitions;
  }
}
