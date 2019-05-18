<?php

namespace Drupal\read_more_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Provides a 'read more' field type.
 *
 * @FieldType(
 *   id = "read_more",
 *   label = @Translation("Read more"),
 *   category = @Translation("Text"),
 *   default_formatter = "read_more_formatter",
 *   default_widget = "read_more_widget",
 * )
 */
class ReadMoreItem extends FieldItemBase implements FieldItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      // The key 'columns' contains the values that the field will store.
      'columns' => [
        // List the values that the field will save.
        'teaser_value' => [
          'type' => 'text',
          'size' => 'big',
        ],
        'teaser_format' => [
          'type' => 'varchar_ascii',
          'length' => 255,
        ],
        'hidden_value' => [
          'type' => 'text',
          'size' => 'big',
        ],
        'hidden_format' => [
          'type' => 'varchar_ascii',
          'length' => 255,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];
    $properties['teaser_value'] = DataDefinition::create('string')
      ->setLabel(t('Teaser text'));
    $properties['teaser_format'] = DataDefinition::create('filter_format')
      ->setLabel(t('Text format'));
    $properties['hidden_value'] = DataDefinition::create('string')
      ->setLabel(t('Hidden text'));
    $properties['hidden_format'] = DataDefinition::create('filter_format')
      ->setLabel(t('Text format'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $fields = ['teaser_value', 'hidden_value'];

    foreach ($fields as $field) {
      $value = $this->get($field)->getValue();

      if (!empty($value)) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return ['label' => 'Read more'] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    $element['label'] = [
      '#title' => $this->t('"Read more" label'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('label'),
    ];

    return $element;
  }

}
