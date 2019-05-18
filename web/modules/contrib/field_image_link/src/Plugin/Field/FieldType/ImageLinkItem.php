<?php

namespace Drupal\field_image_link\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\Core\Url;
use Drupal\field_image_link\ImageLinkItemInterface;
use Drupal\image\Plugin\Field\FieldType\ImageItem;

/**
 * Plugin implementation of the 'image_link' field type.
 *
 * @FieldType(
 *   id = "image_link",
 *   label = @Translation("Image with Link"),
 *   description = @Translation("This field stores the ID of an image file as an integer value and link URL with title."),
 *   category = @Translation("Reference"),
 *   default_widget = "image_link",
 *   default_formatter = "image_link",
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
 *   },
 *   list_class = "\Drupal\file\Plugin\Field\FieldType\FileFieldItemList",
 *   constraints = {"ReferenceAccess" = {}, "FileValidation" = {}, "LinkAccess" = {}, "LinkExternalProtocols" = {}, "LinkNotExistingInternal" = {}}
 * )
 */
class ImageLinkItem extends ImageItem implements ImageLinkItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    $settings = [
      'link_title' => DRUPAL_OPTIONAL,
      'link_type' => ImageLinkItemInterface::LINK_GENERIC,
      'file_extensions' => 'png gif jpg jpeg svg',
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
        'alt' => [
          'description' => "Alternative image text, for the image's 'alt' attribute.",
          'type' => 'varchar',
          'length' => 512,
        ],
        'title' => [
          'description' => "Image title text, for the image's 'title' attribute.",
          'type' => 'varchar',
          'length' => 1024,
        ],
        'width' => [
          'description' => 'The width of the image in pixels.',
          'type' => 'int',
          'unsigned' => TRUE,
        ],
        'height' => [
          'description' => 'The height of the image in pixels.',
          'type' => 'int',
          'unsigned' => TRUE,
        ],
        'link_uri' => [
          'description' => 'The URI of the link.',
          'type' => 'varchar',
          'length' => 2048,
        ],
        'link_title' => [
          'description' => 'The link text.',
          'type' => 'varchar',
          'length' => 255,
        ],
        'link_options' => [
          'description' => 'Serialized array of options for the link.',
          'type' => 'blob',
          'size' => 'big',
          'serialize' => TRUE,
        ],
        'link_display_settings' => [
          'description' => 'Serialized array of settings for the link display.',
          'type' => 'blob',
          'size' => 'big',
          'serialize' => TRUE,
        ],
      ],
      'indexes' => [
        'target_id' => ['target_id'],
        'link_uri' => [['link_uri', 30]],
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

    $properties['link_uri'] = DataDefinition::create('uri')
      ->setLabel(t('URI'));

    $properties['link_title'] = DataDefinition::create('string')
      ->setLabel(t('Link text'));

    $properties['link_options'] = MapDataDefinition::create()
      ->setLabel(t('Options'));

    $properties['link_display_settings'] = MapDataDefinition::create()
      ->setLabel(t('Display settings'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    // Get base form from FileItem.
    $element = parent::fieldSettingsForm($form, $form_state);

    $element['link_type'] = [
      '#type' => 'radios',
      '#title' => t('Allowed link type'),
      '#default_value' => $this->getSetting('link_type'),
      '#options' => [
        static::LINK_INTERNAL => t('Internal links only'),
        static::LINK_EXTERNAL => t('External links only'),
        static::LINK_GENERIC => t('Both internal and external links'),
      ],
      '#weight' => 20,
    ];

    $element['link_title'] = [
      '#type' => 'radios',
      '#title' => t('Allow link text'),
      '#default_value' => $this->getSetting('link_title'),
      '#options' => [
        DRUPAL_DISABLED => t('Disabled'),
        DRUPAL_OPTIONAL => t('Optional'),
        DRUPAL_REQUIRED => t('Required'),
      ],
      '#weight' => 21,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function isExternal() {
    return $this->getUrl()->isExternal();
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl() {
    return Url::fromUri($this->link_uri, (array) $this->link_options);
  }

}
