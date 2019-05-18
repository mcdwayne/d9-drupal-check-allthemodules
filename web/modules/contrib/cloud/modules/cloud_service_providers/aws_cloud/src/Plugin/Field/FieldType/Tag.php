<?php

namespace Drupal\aws_cloud\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'tag' field type.
 *
 * @FieldType(
 *   id = "tag",
 *   label = @Translation("Tag"),
 *   description = @Translation("AWS Tag field"),
 *   default_widget = "tag_item",
 *   default_formatter = "tag_formatter"
 * )
 */
class Tag extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'max_length' => 255,
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['tag_key'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Key'))
      ->setSetting('case_sensitive', $field_definition->getSetting('case_sensitive'))
      ->setRequired(TRUE);

    $properties['tag_value'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Value'))
      ->setSetting('case_sensitive', $field_definition->getSetting('case_sensitive'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'tag_key' => [
          'type' => 'varchar',
          'length' => (int) $field_definition->getSetting('max_length'),
        ],
        'tag_value' => [
          'type' => 'varchar',
          'length' => (int) $field_definition->getSetting('max_length'),
        ],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $elements = [];

    $elements['max_length'] = [
      '#type' => 'number',
      '#title' => t('Maximum length'),
      '#default_value' => $this->getSetting('max_length'),
      '#required' => TRUE,
      '#description' => t('The maximum length of the field in characters.'),
      '#min' => 1,
      '#disabled' => $has_data,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $key = $this->get('tag_key')->getValue();
    $value = $this->get('tag_value')->getValue();
    return empty($key) && empty($value);
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    // Treat the values as property value of the main property, if no array is
    // given.
    if (isset($values) && !is_array($values)) {
      $values = [static::mainPropertyName() => $values];
    }
    if (isset($values)) {
      $values += [
        'options' => [],
      ];
    }
    // Unserialize the values.
    // @todo The storage controller should take care of this, see
    //   SqlContentEntityStorage::loadFieldItems, see
    //   https://www.drupal.org/node/2414835
    if (is_string($values['options'])) {
      $values['options'] = unserialize($values['options']);
    }
    parent::setValue($values, $notify);
  }

  /**
   * Get the tag_key.
   */
  public function getTagKey() {
    return $this->get('tag_key')->getValue();
  }

  /**
   * Get the tag_value.
   */
  public function getTagValue() {
    return $this->get('tag_value')->getValue();
  }

  /**
   * Set the tag_key.
   *
   * @param string $tag_key
   *   The tag key.
   *
   * @return $this
   */
  public function setTagKey($tag_key) {
    return $this->set('tag_key', $tag_key);
  }

  /**
   * Set the tag_value.
   *
   * @param string $tag_value
   *   The tag value.
   *
   * @return $this
   */
  public function setTagValue($tag_value) {
    return $this->set('tag_value', $tag_value);
  }

}
