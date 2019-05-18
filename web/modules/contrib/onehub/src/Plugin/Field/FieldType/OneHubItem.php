<?php

namespace Drupal\onehub\Plugin\Field\FieldType;

use Drupal\file\Plugin\Field\FieldType\FileItem;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'onehub' field type.
 *
 * @FieldType(
 *   id = "onehub",
 *   label = @Translation("OneHub File"),
 *   description = @Translation("This field stores the OneHub data in the onehub table."),
 *   category = @Translation("Reference"),
 *   default_widget = "onehub_file",
 *   default_formatter = "onehub_formatter",
 *   list_class = "\Drupal\file\Plugin\Field\FieldType\FileFieldItemList",
 *   constraints = {"ReferenceAccess" = {}, "FileValidation" = {}}
 * )
 */
class OneHubItem extends FileItem {

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
    $settings = [
      'file_extensions' => 'txt doc docx pdf xls xlsx',
      'workspace' => '',
      'folder' => '',
    ] + parent::defaultFieldSettings();

    // Remove the FileItem defaults.
    unset($settings['description_field']);
    unset($settings['uri_scheme']);

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
        'workspace' => [
          'description' => "The Workspace for the OneHub file",
          'type' => 'varchar',
          'length' => 512,
        ],
        'folder' => [
          'description' => "The OneHub folder this is in.",
          'type' => 'varchar',
          'length' => 1024,
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
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element['#attached']['library'][] = 'file/drupal.file';
    return $element;

  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['workspace'] = DataDefinition::create('string')
      ->setLabel(t('Workspace'));

    $properties['folder'] = DataDefinition::create('string')
      ->setLabel(t('Folder'));

    return $properties;
  }


  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    // Maybe put some sort of tokenized auto save thing.
    // Would change what we had going on above.
    $element = parent::fieldSettingsForm($form, $form_state);
    unset($element['file_directory']);

    return $element;
  }

  /**
   * Determines whether an item should be displayed when rendering the field.
   *
   * @return bool
   *   TRUE if the item should be displayed, FALSE if not.
   */
  public function isDisplayed() {
    return TRUE;
  }
}
