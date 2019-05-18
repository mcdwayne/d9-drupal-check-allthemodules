<?php

namespace Drupal\feed_block\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'rss_feed_field' field type.
 *
 * @FieldType(
 *   id = "rss_feed_field",
 *   label = @Translation("RSS Feed"),
 *   description = @Translation("Configure & display content from an RSS Feed"),
 *   default_widget = "rss_feed_widget",
 *   default_formatter = "rss_feed_formatter"
 * )
 */
class RSSFeedField extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['feed_uri'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Feed URI'))
      ->setSetting('case_sensitive', TRUE)
      ->setRequired(FALSE);
    $properties['count'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Count'))
      ->setSetting('case_sensitive', TRUE)
      ->setRequired(FALSE);
    $properties['display_date'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Display date'))
      ->setSetting('case_sensitive', TRUE)
      ->setRequired(FALSE);
    $properties['date_format'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Date format'))
      ->setSetting('case_sensitive', TRUE)
      ->setRequired(FALSE);
    $properties['custom_date_format'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Date format'))
      ->setSetting('case_sensitive', TRUE)
      ->setRequired(FALSE);
    $properties['display_description'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Display description'))
      ->setSetting('case_sensitive', TRUE)
      ->setRequired(FALSE);
    $properties['description_length'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Description length'))
      ->setSetting('case_sensitive', TRUE)
      ->setRequired(FALSE);
    $properties['description_plaintext'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Description plaintext'))
      ->setSetting('case_sensitive', TRUE)
      ->setRequired(FALSE);
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'feed_uri' => [
          'type' => 'varchar_ascii',
          'length' => 512,
          'binary' => TRUE,
        ],
        'count' => [
          'type' => 'int',
          'not null' => TRUE,
          'default' => 5,
        ],
        'display_date' => [
          'type' => 'int',
          'size' => 'tiny',
        ],
        'date_format' => [
          'type' => 'varchar_ascii',
          'length' => 255,
          'binary' => TRUE,
        ],
        'custom_date_format' => [
          'type' => 'varchar_ascii',
          'length' => 255,
          'binary' => TRUE,
        ],
        'display_description' => [
          'type' => 'int',
          'size' => 'tiny',
        ],
        'description_length' => [
          'type' => 'int',
          'size' => 'small',
        ],
        'description_plaintext' => [
          'type' => 'int',
          'size' => 'tiny',
        ],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $values['intro_text'] = Random::paragraph(5);
    $values['feed_uri'] = 'https://www.drupal.org/project/project_module/feed/full';
    $values['count'] = array_rand(range(1, 10), 1);
    $values['display_date'] = array_rand(range(0, 1), 1);
    $values['date_format'] = 'small';
    $values['display_description'] = array_rand(range(0, 1), 1);
    $values['description_length'] = 255;
    $values['description_plaintext'] = 1;
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $feed_uri = $this->get('feed_uri')->getValue();
    return $feed_uri === NULL || $feed_uri === '';
  }

}
