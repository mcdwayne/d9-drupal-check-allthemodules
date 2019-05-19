<?php

namespace Drupal\strava_activities\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\StringLongItem;

/**
 * Defines the 'strava_map_polyline' field type.
 *
 * @FieldType(
 *   id = "strava_map_polyline",
 *   label = @Translation("Map Polyline String"),
 *   description = @Translation("A field containing a long string value containing an encoded polyline."),
 *   category = @Translation("Text"),
 *   default_widget = "string_textarea",
 *   default_formatter = "strava_map_polyline",
 * )
 */
class StravaMapPolylineItem extends StringLongItem {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => $field_definition->getSetting('case_sensitive') ? 'blob' : 'text',
          'size' => 'big',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();
    $values['value'] = $random->paragraphs();
    return $values;
  }

}
