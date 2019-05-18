<?php

namespace Drupal\reference_table_formatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;

/**
 * Interface required to implement a reference field formatter.
 */
interface FormatterInterface {

  /**
   * The the entity ID from the field value.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The reference field to gain the ID for.
   *
   * @return int
   *   An entity ID.
   */
  public function getEntityIdFromFieldItem(FieldItemInterface $item);

  /**
   * Get the target bundle from a reference field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition to check the target bundle.
   *
   * @return string
   *   The bundle that is the target of the field.
   *
   * @throws \Exception
   */
  public function getTargetBundleId(FieldDefinitionInterface $field_definition);

  /**
   * Get the entity which is the target of the reference field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   *
   * @return string
   *   The entity which is the target of the reference.
   */
  public function getTargetEntityId(FieldDefinitionInterface $field_definition);

  /**
   * Get the view modes which can be selected for this field formatter.
   */
  public function getConfigurableViewModes();

}
