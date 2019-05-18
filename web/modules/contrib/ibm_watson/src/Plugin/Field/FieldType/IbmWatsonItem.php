<?php

namespace Drupal\ibm_watson\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\file\Plugin\Field\FieldType\FileItem;
use Drupal\file\Entity\file;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a field type of IBM Watson.
 *
 * @FieldType(
 *   id = "ibm_watson",
 *   label = @Translation("Ibm Watson"),
 *   description = @Translation("An entity field containing an entity reference."),
 *   category = @Translation("Reference"),
 *   no_ui = FALSE,
 *   class = "\Drupal\ibm_watson\Plugin\Field\FieldType\IbmWatsonItem",
 *   default_formatter = "ibm_watson",
 *   default_widget = "ibm_watson"
 * )
 */
class IbmWatsonItem extends FileItem {
  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    $settings = [
      'file_extensions' => 'wav',
    ] + parent::defaultFieldSettings();

    unset($settings['description_field']);
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {

    return [
      'columns' => [
        'target_id' => [
          'description' => 'The ID of the file entity.',
          'type' => 'int',
          'unsigned' => TRUE,
        ],
        'mimetype' => [
          'type' => 'text',
          'size' => 'tiny',
          'not null' => FALSE,
        ],
        'language' => [
          'type' => 'text',
          'size' => 'tiny',
          'not null' => FALSE,
        ],
        'translate_text' => [
          'type' => 'text',
          'size' => 'big',
          'not null' => FALSE,
        ],
      ],
      'indexes' => [
        'target_id' => ['target_id'],
      ],
      'foreign keys' => [
        'target_id' => [
          'table' => 'file_managed',
          'columns' => ['target_id' => 'fid'],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);
    $properties['mimetype'] = DataDefinition::create('string');
    $properties['translate_text'] = DataDefinition::create('string');
    $properties['language'] = DataDefinition::create('string')
      ->setLabel(t('Language'));
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {

    // Get base form from FileItem.
    $element = parent::fieldSettingsForm($form, $form_state);

    // Remove the description option.
    unset($element['description_field']);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    parent::preSave();
    $translate_text = $this->translate_text;

    // Determine Mime type.
    $file = File::load($this->target_id);
    $mimetype = ibm_watson_mime_type($file->getMimeType());
    $this->mimetype = $mimetype;

    // Determine the translate text if necessary.
    if (empty($translate_text)) {
      $uri = $file->getFileUri();
      $real_path = drupal_realpath($uri);

      // $result = ibm_watson_recognize_curl($url, $real_path, $mimetype);.
      $result = ibm_watson_sessionless_recognize($real_path, $mimetype, $this->language);
      $this->translate_text = $result;
    }
  }

}
