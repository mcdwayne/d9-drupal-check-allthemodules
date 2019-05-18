<?php

namespace Drupal\regex_redirect\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'link' field type for redirect source.
 *
 * @FieldType(
 *   id = "regex_redirect_source",
 *   label = @Translation("Regex Redirect source"),
 *   description = @Translation("Stores a regex redirect source"),
 *   default_widget = "regex_redirect_source",
 *   default_formatter = "regex_redirect_source",
 *   no_ui = TRUE
 * )
 */
class RegexRedirectSourceItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['path'] = DataDefinition::create('string')
      ->setLabel(t('Path'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'path' => [
          'description' => 'The source path',
          'type' => 'varchar',
          'length' => 2048,
        ],
      ],
      'indexes' => [
        'path' => [['path', 50]],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    // Set random length for the path.
    $domain_length = mt_rand(7, 15);
    $random = new Random();

    $values['path'] = 'http://www.' . $random->word($domain_length);

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return $this->path === NULL || $this->path === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'path';
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl() {
    return Url::fromUri('base:' . $this->path);
  }

}
