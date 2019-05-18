<?php

namespace Drupal\image_tagger\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\image\Plugin\Field\FieldType\ImageItem;

/**
 * Defines the 'image_tagger_image_tagger_field' field type.
 *
 * @FieldType(
 *   id = "image_tagger_image_tagger_field",
 *   label = @Translation("Image tagger field"),
 *   description = @Translation("This field stores the ID of an image file as an integer value, along with coordinates"),
 *   category = @Translation("Reference"),
 *   default_widget = "image_tagger_image_tagger_widget",
 *   default_formatter = "image_tagger_image_tagger_formatter",
 *   column_groups = {
 *     "file" = {
 *       "label" = @Translation("File"),
 *       "columns" = {
 *         "target_id", "width", "height"
 *       },
 *       "require_all_groups_for_translation" = TRUE
 *     },
 *     "alt" = {
 *       "label" = @Translation("Alt"),
 *       "translatable" = TRUE
 *     },
 *     "title" = {
 *       "label" = @Translation("Title"),
 *       "translatable" = TRUE
 *     },
 *     "data" = {
 *       "label" = @Translation("Serialized data for points"),
 *       "translatable" = FALSE
 *     },
 *   },
 *   list_class = "\Drupal\file\Plugin\Field\FieldType\FileFieldItemList",
 *   constraints = {"ReferenceAccess" = {}, "FileValidation" = {}}
 * )
 */
class ImageTaggerFieldItem extends ImageItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    $settings = parent::defaultFieldSettings();
    $settings['entity_type'] = 'node';
    $settings['view_mode'] = 'full';
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $settings = $this->getSettings();
    $element = parent::fieldSettingsForm($form, $form_state);
    $definitions = $this->getEntityManager()->getDefinitions();
    $options = [];
    foreach ($definitions as $type => $definition) {
      $options[$type] = $definition->getLabel();
    }
    $element['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity type'),
      '#options' => $options,
      '#required' => TRUE,
      '#description' => $this->t('What entity type should be available for autocomplete?'),
      '#default_value' => !empty($settings['entity_type']) ? $settings['entity_type'] : '',
    ];
    // @todo: Actually fetch the view modes here.
    $element['view_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('View mode'),
      '#options' => [
        'full' => 'full',
        'teaser' => 'teaser',
      ],
      '#required' => TRUE,
      '#description' => $this->t('What view mode should be used in the entity preview?'),
      '#default_value' => !empty($settings['view_mode']) ? $settings['view_mode'] : '',
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);
    $schema['columns']['data'] = [
      'description' => 'The image tagger data',
      'type' => 'text',
      'size' => 'big',
    ];
    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);
    $properties['data'] = DataDefinition::create('string')
      ->setLabel(t('Data'))
      ->setDescription(t("Image tagger data."));
    return $properties;
  }

}
