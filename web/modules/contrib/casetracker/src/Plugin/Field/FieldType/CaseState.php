<?php

/**
 * @file
 * Contains Drupal\casetracker\Plugin\FieldType\CaseStatus
 */

namespace Drupal\casetracker\Plugin\Field\FieldType;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 *  Plugin implementation of the 'casetracker_status' field type.
 *
 * @FieldType(
 *   id = "casetracker_state",
 *   label = @Translation("Case Tracker Status"),
 *   module = "casetracker",
 *   description = @Translation("Tracks statuses of cases."),
 *   default_widget = "casetracker_state_widget",
 *   default_formatter = "casetracker_state_formatter"
 * )
 */
class CaseStatus extends FieldItemBase
{
  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'status' => array(
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'description' => 'Status of case',
        ),
        'priority' => array(
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'description' => 'Priority'
        ),
        'assigned_to' => array(
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'description' => 'User id of assigned user',
        ),
        'is_open' => array(
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'description' => 'Indicates whether the case is open.'
        ),
        'case_type' => array(
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'description' => 'Category or type of case',
        ),
        'opened' => array(
          'type' => 'int',
          'not null' => FALSE,
          'description' => 'Date the case was last opened.',
        ),
      ),
    );
  }

  /**
   * Defines properties for casetracker.
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['status'] = DataDefinition::create('integer')
      ->setRequired(TRUE)
      ->setLabel(t('Status'));
    $properties['priority'] = DataDefinition::create('integer')
      ->setRequired(TRUE)
      ->setLabel(t('Priority'));
    $properties['assigned_to'] = DataDefinition::create('integer')
      ->setLabel('Assigned To');
    $properties['is_open'] = DataDefinition::create('integer')
      ->setLabel('Is Opened');
    $properties['case_type'] = DataDefinition::create('integer')
      ->setLabel('Type');
    $properties['opened'] = DataDefinition::create('integer')
      ->setRequired(TRUE)
      ->setReadOnly(TRUE)
      ->setLabel('Date Last Opened');
    return $properties;
  }

}