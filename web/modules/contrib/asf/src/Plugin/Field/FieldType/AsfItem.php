<?php

namespace Drupal\Asf\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\asf\AsfSchema;
use Drupal\asf;

/**
 * Plugin implementation of the 'asf' field type.
 *
 * @FieldType(
 *   id = "asf",
 *   label = @Translation("ASF"),
 *   module = "asf",
 *   description = @Translation("Demonstrates a field composed of an RGB color."),
 *   default_widget = "AsfWidget",
 *   default_formatter = "AsfFormatter"
 * )
 */
class AsfItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'publication_type' => array(
          'type' => 'text',
          'size' => 'tiny',
          'not null' => FALSE,
        ),
        'startdate' => array(
          'type' => 'int',
          'size' => 'big',
          'not null' => FALSE,
        ),
        'enddate' => array(
          'type' => 'int',
          'size' => 'big',
          'not null' => FALSE,
        ),
        'start_time' => array(
          'type' => 'int',
          'size' => 'big',
          'not null' => FALSE,
        ),
        'end_time' => array(
          'type' => 'int',
          'size' => 'big',
          'not null' => FALSE,
        ),
        'iteration_day' => array(
          'type' => 'text',
          'size' => 'tiny',
          'not null' => FALSE,
        ),
        'iteration_week' => array(
          'type' => 'text',
          'size' => 'tiny',
          'not null' => FALSE,
        ),
        'iteration_weekday' => array(
          'type' => 'text',
          'size' => 'tiny',
          'not null' => FALSE,
        ),
        'iteration_month' => array(
          'type' => 'text',
          'size' => 'tiny',
          'not null' => FALSE,
        ),
        'iteration_year' => array(
          'type' => 'text',
          'size' => 'tiny',
          'not null' => FALSE,
        ),
        'iteration_max' => array(
          'type' => 'int',
          'size' => 'tiny',
          'not null' => FALSE,
        ),
        'iteration_end' => array(
          'type' => 'int',
          'size' => 'tiny',
          'not null' => FALSE,
        ),
        'inherit_eid' => array(
          'type' => 'int',
          'size' => 'big',
          'not null' => FALSE,
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('publication_type')->getValue();
    return $value == ASF_TYPE_NOTHING || $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    //Strings
    $properties['publication_type'] = DataDefinition::create('string')
      ->setLabel(t('Publication type'));
    $properties['start_time'] = DataDefinition::create('any')
      ->setLabel(t('Start time'));
    $properties['end_time'] = DataDefinition::create('any')
      ->setLabel(t('End time'));
    $properties['iteration_day'] = DataDefinition::create('string')
      ->setLabel(t('Iteration day'));
    $properties['iteration_week'] = DataDefinition::create('string')
      ->setLabel(t('Iteration week'));
    $properties['iteration_weekday'] = DataDefinition::create('string')
      ->setLabel(t('Iteration weekday'));
    $properties['iteration_month'] = DataDefinition::create('string')
      ->setLabel(t('Iteration month'));
    $properties['iteration_year'] = DataDefinition::create('string')
      ->setLabel(t('Iteration year'));
    //Ints
    $properties['startdate'] = DataDefinition::create('any')
      ->setLabel(t('Start date'));
    $properties['enddate'] = DataDefinition::create('any')
      ->setLabel(t('End date'));
    $properties['iteration_max'] = DataDefinition::create('string')
      ->setLabel(t('Max iterations'));
    $properties['iteration_end'] = DataDefinition::create('string')
      ->setLabel(t('iterations end'));
    $properties['inherit_eid'] = DataDefinition::create('string')
      ->setLabel(t('Inherit scheme'));
    return $properties;
  }


  /**
   * Presave (change dates to unix timestamps)
   */
  public function preSave() {
    //Start and end times.
    if (!is_numeric($this->get('start_time')->getValue())) {
      //$startdate = strtotime($this->get('startdate')->getValue());
      $startDate = $this->get('start_time')->getValue();
      $start_time = isset($startDate) ? $startDate->format('U') : 0;
    }
    else {
      $start_time = $this->get('start_time')->getValue();
    }
    $this->set('start_time', $start_time);
    if ($this->get('end_time')->getValue() == '') {
      $this->set('end_time', 0);
    }
    else {
      if (is_numeric($this->get('end_time')->getValue())) {
        $end_time = $this->get('end_time')->getValue();
      }
      else {
        $endDate = $this->get('end_time')->getValue();
        $end_time = isset($endDate) ? $endDate->format('U'): 0;
      }
      $this->set('end_time', $end_time);
    }

    //Start and end dates

    if (!is_numeric($this->get('startdate')->getValue())) {
      //$startdate = strtotime($this->get('startdate')->getValue());
      $startDate = $this->get('startdate')->getValue();
      $startdate = $startDate->format('U');
    }
    else {
      $startdate = $this->get('startdate')->getValue();
    }
    $this->set('startdate', $startdate);

    if ($this->get('enddate')->getValue() == '') {
      $this->set('enddate', 0);
    }
    else {
      if (is_numeric($this->get('enddate')->getValue())) {
        $enddate = $this->get('enddate')->getValue();
      }
      else {
        $endDate = $this->get('enddate')->getValue();
        $enddate = $endDate->format('U');
      }
      $this->set('enddate', $enddate);
    }


    $iteration_max = $this->get('iteration_max')->getValue();
    if ($iteration_max == '') {
      $iteration_max = 0;
    }
    $this->set('iteration_max', $iteration_max);


    $iteration_end = strtotime($this->get('iteration_end')->getValue());
    if ($iteration_end == '') {
      $iteration_end = 0;
    }
    $this->set('iteration_end', $iteration_end);


    $inherit_eid = strtotime($this->get('inherit_eid')->getValue());
    if ($inherit_eid == '') {
      $inherit_eid = 0;
    }
    $this->set('inherit_eid', $inherit_eid);
  }


  /**
   * @param bool $update
   */
  public function postSave($update) {
    parent::postSave($update);
    $def = $this->getFieldDefinition();
    $fieldName = $def->getName();
    $entity = $this->getEntity();

    //Todo Add delta to the field name here.
    // Now this is Broken, only one field schema will be used.
    $schema = new AsfSchema();
    $schema->deleteSchedule($entity->id(), $fieldName);
    $schema->generateScheme($entity, $this->getValue(), $fieldName);
  }
}
