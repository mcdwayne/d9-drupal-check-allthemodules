<?php

/**
 * @file
 * Contains \Drupal\file_chooser_field\Plugin\Field\FieldType\FileChooserFieldFileItem.
 */

namespace Drupal\file_chooser_field\Plugin\Field\FieldType;

use Drupal\Component\Utility\Bytes;
use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\file\Entity\File;
use Drupal\file\Plugin\Field\FieldType;
use Drupal\file\Plugin\Field\FieldType\FileItem;

/**
 * Plugin implementation of the 'file' field type.
 *
 * @FieldType(
 *   id = "file_chooser_field_file",
 *   label = @Translation("File"),
 *   description = @Translation("This field stores the ID of a file as an integer value."),
 *   category = @Translation("File Chooser Field"),
 *   default_widget = "file_generic",
 *   default_formatter = "file_default",
 *   list_class = "\Drupal\file\Plugin\Field\FieldType\FileFieldItemList",
 *   constraints = {"ValidReference" = {}, "ReferenceAccess" = {}}
 * )
 */
class FileChooserFieldFileItem extends FileItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return parent::schema($field_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    return parent::propertyDefinitions($field_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = parent::storageSettingsForm($form, $form_state, $has_data);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::fieldSettingsForm($form, $form_state);
    $settings = $this->getSettings();
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function validateDirectory($element, FormStateInterface $form_state) {
    parent::validateDirectory($element, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public static function validateExtensions($element, FormStateInterface $form_state) {
    parent::validateExtensions($element, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public static function validateMaxFilesize($element, FormStateInterface $form_state) {
    parent::validateMaxFilesize($element, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getUploadLocation($data = array()) {
    return parent::getUploadLocation($data);
  }

  /**
   * {@inheritdoc}
   */
  public function getUploadValidators() {
    return parent::getUploadValidators();
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();
    $settings = $field_definition->getSettings();

    // Generate a file entity.
    $destination = $settings['uri_scheme'] . '://' . $settings['file_directory'] . $random->name(10, TRUE) . '.txt';
    $data = $random->paragraphs(3);
    $file = file_save_data($data, $destination, FILE_EXISTS_ERROR);
    $values = array(
      'target_id' => $file->id(),
      'display' => (int)$settings['display_default'],
      'description' => $random->sentences(10),
    );
    return $values;
  }

  /**
   * Determines whether an item should be displayed when rendering the field.
   *
   * @return bool
   *   TRUE if the item should be displayed, FALSE if not.
   */
  public function isDisplayed() {
    return parent::isDisplayed();
  }

}
