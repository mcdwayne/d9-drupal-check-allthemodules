<?php

namespace Drupal\twitter_username\Plugin\Field\FieldType;


use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'text' field type.
 *
 * @FieldType(
 *   id = "twitter_username",
 *   label = @Translation("Twitter username"),
 *   description = @Translation("This field is for twitter usernames."),
 *   category = @Translation("General"),
 *   default_widget = "twitter_username_textfield",
 *   default_formatter = "twitter_username_default"
 * )
 */
class TwitterUsername extends FieldItemBase {

  const TWITTER_USERNAME_MAX_LENGTH = 16;

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Twitter Username'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'value' => array(
          'type' => 'varchar',
          'length' => static::TWITTER_USERNAME_MAX_LENGTH,
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }
}
