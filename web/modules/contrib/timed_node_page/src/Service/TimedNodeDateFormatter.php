<?php

namespace Drupal\timed_node_page\Service;

use Drupal\Core\Entity\EntityFieldManagerInterface;

/**
 * Class TimedNodeDateFormatter.
 *
 * @package Drupal\timed_node_page\Service
 */
class TimedNodeDateFormatter {

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * An array of field definitions.
   *
   * @var array
   */
  private $definitions = [];

  /**
   * TimedNodeDateFormatter constructor.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager service.
   */
  public function __construct(
    EntityFieldManagerInterface $entityFieldManager
  ) {
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * Determines the date field type.
   *
   * @param string $field
   *   Existing date field on the node bundle.
   * @param string $bundle
   *   The bundle on which we search for the field.
   *
   * @return string
   *   The field type name.
   */
  public function getFieldType($field, $bundle) {
    $definitions = $this->getDefinitions();

    if (!isset($definitions[$field])) {
      throw new \LogicException(sprintf('Field %s not found on node %s.', $field, $bundle));
    }
    /** @var \Drupal\Core\Field\FieldStorageDefinitionInterface $definition */
    $definition = $definitions[$field];

    return $definition->getSetting('datetime_type');
  }

  /**
   * Formats date for the type of the date field.
   *
   * This is used to enable date comparision of drupal dates in query.
   *
   * @param string $field
   *   The date field name.
   * @param string $bundle
   *   The bundle of the node.
   * @param string $time
   *   The date.
   *
   * @return string
   *   The formatted date.
   */
  public function formatDateForField($field, $bundle, $time = 'now') {
    $fieldType = $this->getFieldType($field, $bundle);
    $drupalDate = new \DateTime($time, new \DateTimezone(DATETIME_STORAGE_TIMEZONE));

    if ($fieldType == 'datetime') {
      return $drupalDate->format(DATETIME_DATETIME_STORAGE_FORMAT);
    }
    elseif ($fieldType == 'date') {
      return $drupalDate->format(DATETIME_DATE_STORAGE_FORMAT);
    }

    throw new \LogicException(sprintf('Unsupported field type for date: %s', $fieldType));
  }

  /**
   * Returns field storage definitions.
   *
   * @return \Drupal\Core\Field\FieldStorageDefinitionInterface[]
   *   An array of definitions.
   */
  private function getDefinitions() {
    if (!$this->definitions) {
      $this->definitions = $this->entityFieldManager
        ->getFieldStorageDefinitions('node');
    }

    return $this->definitions;
  }

}
