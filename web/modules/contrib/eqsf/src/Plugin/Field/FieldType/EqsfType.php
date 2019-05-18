<?php

namespace Drupal\eqsf\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\eqsf\EqsfSchema;

/**
 * Plugin implementation of the 'eqsf_field' field type.
 *
 * @FieldType(
 *   id = "eqsf_field",
 *   label = @Translation("Entityqueue Scheduler"),
 *   category = @Translation("Entityqueue Scheduler"),
 *   module = "eqsf",
 *   default_widget = "eqsf_widget",
 *   default_formatter = "eqsf_formatter"
 * )
 */
class EqsfType extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['select'] = DataDefinition::create('any')
      ->setLabel(new TranslatableMarkup('select'));

    $properties['position'] = DataDefinition::create('any')
      ->setLabel(new TranslatableMarkup('position'));

    $properties['startdate'] = DataDefinition::create('any')
      ->setLabel(new TranslatableMarkup('start date'));

    $properties['enddate'] = DataDefinition::create('any')
      ->setLabel(new TranslatableMarkup('end date'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'select'    => [
          'type'     => 'text',
          'size'     => 'big',
          'not null' => FALSE,
        ],
        'startdate' => [
          'type'     => 'int',
          'size'     => 'big',
          'not null' => FALSE,
        ],
        'enddate'   => [
          'type'     => 'int',
          'size'     => 'big',
          'not null' => FALSE,
        ],
        'position'  => [
          'type'     => 'int',
          'unsigned' => FALSE,
          'size'     => 'small',
          'not null' => FALSE,
        ],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();
    // $values['value'] = .
    // $random->word(mt_rand(1, $field_definition->getSetting('max_length')));
    // return $values;.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $elements = [];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('select')->getValue();
    // $value = null;.
    return $value == "empty" || $value === NULL || $value === '';
  }

  public function preSave() {
    $position = $this->get('position')->getValue();
    $this->set('position', $position);

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
  }

  /**
   * @param bool $update
   */
  public function postSave($update) {
    parent::postSave($update);
    $entity = $this->getEntity();
    $eqid = $this->getValue()['select'];
    $schema = new EqsfSchema();
    $schema->deleteSchedule($entity->id());
    $schema->generateScheme($entity, $eqid, $this->getValue());
  }

}
