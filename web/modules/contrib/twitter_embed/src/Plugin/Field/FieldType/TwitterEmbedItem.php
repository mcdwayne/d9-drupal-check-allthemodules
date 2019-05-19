<?php

namespace Drupal\twitter_embed\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\twitter_embed\TwitterWidgetInterface;

/**
 * Plugin implementation of the 'twitter_embed_field' field type.
 *
 * @FieldType(
 *   id = "twitter_embed_field",
 *   label = @Translation("Twitter embed"),
 *   description = @Translation("Twitter embed of timeline and button."),
 *   default_widget = "twitter_embed_widget",
 *   default_formatter = "twitter_timeline_formatter"
 * )
 */
class TwitterEmbedItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      // @todo review
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Text value'))
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
        'value' => [
          'type' => 'varchar_ascii',
          'length' => TwitterWidgetInterface::USERNAME_MAX_LENGTH,
          'binary' => FALSE,
        ],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();
    // @todo to review
    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    // @todo set a list of random accounts
    $random = new Random();
    $values['value'] = $random->word(mt_rand(1, TwitterWidgetInterface::USERNAME_MAX_LENGTH));
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $elements = [];
    // Most of the storage settings are delegated
    // to the FieldFormatter to allow a widget change.
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

}
