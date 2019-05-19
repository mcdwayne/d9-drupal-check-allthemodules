<?php

namespace Drupal\xero\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ListDataDefinition;

/**
 * Xero Schedule Definition
 */
class ScheduleDefinition extends ComplexDataDefinitionBase implements XeroDefinitionInterface {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      $info['Period'] = DataDefinition::create('integer')
        ->setLabel('Period')
        ->setDescription('An integer that corresponds to the Unit property.');
      $info['Unit'] = DataDefinition::create('string')
        ->setLabel('Unit')
        ->addConstraint('Choice', ['WEEKLY', 'MONHTLY']);
      $info['DueDate'] = DataDefinition::create('string')
        ->setLabeL('Due Date')
        ->addConstraint('Date');
      $info['DueDateType'] = DataDefinition::create('string')
        ->setLabel('Due Date Type')
        ->addConstraint('Choice', ['choices' => ['DAYSAFTERBILLDATE', 'DAYSAFTERBILLMONTH', 'OFCURRENTMONTH', 'OFFOLLOWINGMONTH']]);
      $info['StartDate'] = DataDefinition::create('string')
        ->setLabel('Start Date')
        ->addConstraint('Date');
      $info['NextScheduledDate'] = DataDefinition::create('string')
        ->setLabel('Next Scheduled Date')
        ->addConstraint('Date');
      $info['EndDate'] = DataDefinition::create('string')
        ->setLabel('End Date')
        ->addConstraint('Date');

    }
    return $this->propertyDefinitions;
  }
}