<?php

namespace Drupal\convert_media_tags_to_markup\ConvertMediaTagsToMarkup;

use Drupal\convert_media_tags_to_markup\traits\CommonUtilities;
use Drupal\Core\Entity\Entity as DrupalEntity;

/**
 * Represents a Drupal entity for our purposes.
 */
class Entity {

  use CommonUtilities;

  /**
   * Constructor.
   *
   * @param Drupal\Core\Entity\Entity $entity
   *   The Drupal entity for which this object is a wrapper.
   */
  public function __construct(DrupalEntity $entity) {
    $this->entity = $entity;
  }

  /**
   * Process this entity; change the media tags code to an image tag.
   *
   * @param bool $simulate
   *   Whether or not to Simulate the results.
   * @param string $log
   *   For example "print_r" or "dpm".
   *
   * @throws Exception
   */
  public function process(bool $simulate = TRUE, string $log = 'print_r') {
    foreach ($this->entity->getFields() as $fieldname => $field) {
      $this->processField($fieldname, $field, $simulate, $log);
    }
    if ($simulate) {
      $log('Not actually saving entity ' . $this->entity->id() . ' because we are in simulation mode.' . PHP_EOL);
    }
    else {
      $log('Saving entity ' . $this->entity->id() . ' because we are not in simulation mode.' . PHP_EOL);
      $this->entity->save();
    }
  }

  /**
   * Process a field.
   *
   * @param string $fieldname
   *   A field name.
   * @param object $field
   *   A field list object.
   * @param bool $simulate
   *   Whether or not to Simulate the results.
   * @param string $log
   *   For example "print_r" or "dpm".
   *
   * @throws Exception
   */
  public function processField(string $fieldname, $field, bool $simulate = TRUE, $log = 'print_r') {
    $log('Processing field ' . $fieldname . ' of class ' . get_class($field) . ' for entity ' . $this->entity->id() . PHP_EOL);
    $value = $field->getValue();
    foreach ($value as $delta => $row) {
      if (!empty($row['value']) && !empty($row['format'])) {
        $log(' => Item at position ' . $delta . ' is a candidate for processing' . PHP_EOL);
        $value[$delta]['value'] = App::instance()->filterText($row['value']);
        if ($simulate) {
          $log('Simulating changing the content to: ' . PHP_EOL);
          $log($value[$delta]['value']);
          $log(PHP_EOL);
        }
        else {
          $this->entity->{$fieldname} = $value;
          $log('Changed its content.' . PHP_EOL);
        }
      }
      else {
        $log(' => Item at position ' . $delta . ' is not a candidate for processing' . PHP_EOL);
      }
    }
  }

}
