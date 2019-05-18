<?php

/**
 * @file
 * Contains \Drupal\file_compressor_field\Plugin\Field\FieldType\FileCompressorItem.
 */

namespace Drupal\file_compressor_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\field\FieldConfigInterface;
use Drupal\file\Plugin\Field\FieldType\FileItem;

/**
 * Plugin implementation of the 'file' field type.
 *
 * @FieldType(
 *   id = "file_compressor",
 *   label = @Translation("File Compressor"),
 *   description = @Translation("This field stores the ID of a file as an integer value."),
 *   default_formatter = "file_default",
 *   list_class = "\Drupal\file_compressor_field\Plugin\Field\FieldType\FileCompressorFieldItemList",
 *   constraints = {"ValidReference" = {}, "ReferenceAccess" = {}}
 * )
 */
class FileCompressorItem extends FileItem {

  public static function defaultStorageSettings() {
    return array(
      'file_compressor' => 'zip_built_in',
    ) + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return array(
      'compressed_fields' => array(),
    ) + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    unset($properties['description']);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $scheme_options = \Drupal::service('stream_wrapper_manager')->getNames(StreamWrapperInterface::WRITE_VISIBLE);
    $element['uri_scheme'] = array(
      '#type' => 'radios',
      '#title' => t('Upload destination'),
      '#options' => $scheme_options,
      '#default_value' => $this->getSetting('uri_scheme'),
      '#description' => t('Select where the final files should be stored. Private file storage has significantly more overhead than public files, but allows restricted access to files within this field.'),
      '#disabled' => $has_data,
    );

    $file_compressor_options = array_map(function($item){
      return $item['admin_label'];
    }, \Drupal::service('plugin.manager.file_compressor')->getDefinitions());

    $element['file_compressor'] = array(
      '#type' => 'radios',
      '#title' => t('File Compressor'),
      '#options' => $file_compressor_options,
      '#default_value' => $this->getSetting('file_compressor'),
      '#description' => t('Select the file compressor to use.'),
      '#disabled' => $has_data,
    );

      return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);
    unset($schema['description']);

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::fieldSettingsForm($form, $form_state);

    $options = array();
    $field_definitions = $this->getEntity()->getFieldDefinitions();
    foreach ($field_definitions as $field_name =>$field_definition) {
      if ($field_definition instanceof FieldConfigInterface && in_array($field_definition->getType(), array('file', 'image'))) {
        $options[$field_name] = $field_definition->getLabel();
      }
    }

    $form['compressed_fields'] = array(
      '#title' => t('Fields to compress'),
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => $this->getSetting('compressed_fields'),
      '#weight' => 10,
    );

    unset($form['file_extensions']);
    unset($form['max_filesize']);
    unset($form['description_field']);

    return $form;
  }

}
