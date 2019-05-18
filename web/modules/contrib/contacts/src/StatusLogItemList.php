<?php

namespace Drupal\contacts;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Field\FieldItemList;

/**
 * List class for Status Log field items.
 *
 * @package Drupal\drs_submission
 */
class StatusLogItemList extends FieldItemList {

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    parent::preSave();

    $entity = $this->getEntity();

    // Ignore non-content entities.
    if (!$entity instanceof ContentEntityBase) {
      return;
    }

    // Ignore new new entities or where original is not available.
    if (!isset($entity->original)) {
      return;
    }

    $definition = $this->getFieldDefinition();
    $field_name = $definition->getName();
    $source_field = $definition->getFieldStorageDefinition()->getSetting('source_field');
    $property_name = $entity->getFieldDefinition($source_field)->getFieldStorageDefinition()->getMainPropertyName();

    $value = $entity->{$source_field}->{$property_name};
    $original_value = $entity->original->{$source_field}->{$property_name};

    if ($value !== $original_value) {
      $values = [
        'value' => $value,
        'previous' => $original_value,
        'uid' => \Drupal::currentUser()->id(),
        'timestamp' => \Drupal::time()->getRequestTime(),
      ];
      $entity->{$field_name}->appendItem($values);
    }
  }

  /**
   * Gets the timestamp for the first time a value appeared in the log.
   *
   * @param string $value
   *   The value to look for.
   *
   * @return int|null
   *   The timestamp or null.
   */
  public function getTimestamp($value) {
    foreach ($this as $log_item) {
      if ($log_item->value == $value) {
        return $log_item->timestamp;
      }
    }
    return NULL;
  }

  /**
   * Gets the user ID for the first time a value appeared in the log.
   *
   * @param string $value
   *   The value to look for.
   *
   * @return int|null
   *   The user id or null.
   */
  public function getUser($value) {
    foreach ($this as $log_item) {
      if ($log_item->value == $value) {
        return $log_item->uid;
      }
    }
    return NULL;
  }

}
