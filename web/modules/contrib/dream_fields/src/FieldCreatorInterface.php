<?php

namespace Drupal\dream_fields;

/**
 * An interface for field creation.
 */
interface FieldCreatorInterface {

  /**
   * Create fields from the field builder object.
   *
   * @param \Drupal\dream_fields\FieldBuilderInterface $field_builder
   *   The field builder to commit to the database.
   */
  public function save(FieldBuilderInterface $field_builder);

  /**
   * Returns an instance of the field builder.
   *
   * @return \Drupal\dream_fields\FieldBuilderInterface
   *   A field builder.
   */
  public static function createBuilder();

}
