<?php

namespace Drupal\google_vision;

/**
 * Interface for GoogleVisionHelper.
 */
interface GoogleVisionHelperInterface {

  /**
   * Set the value for the alternative text field of the image file.
   *
   * @param \Drupal\file\FileInterface $file
   *  The file entity.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field
   *  The field definition.
   */
  public function setAltText($file, $field);

  /**
   * Edit the current value of the Alt Text field of the image file.
   *
   * @param \Drupal\file\FileInterface $file
   *  The file entity.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field
   *  The field definition.
   * @param string $value
   *  The Alt Text field value.
   */
  public function editAltText($file, $field, $value);

}
