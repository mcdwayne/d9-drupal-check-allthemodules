<?php

namespace Drupal\ckeditor5_sections\Plugin\EntityUsage\Track;

use Drupal\Component\Utility\Html;
use Drupal\entity_usage\Plugin\EntityUsage\Track\TextFieldEmbedBase;

/**
 * Tracks usage of medias in sections fields.
 *
 * @EntityUsageTrack(
 *   id = "sections_media",
 *   label = @Translation("Sections media"),
 *   description = @Translation("Tracks relationships created with Sections in formatted text fields."),
 *   field_types = {"text_long", "text_with_summary"},
 * )
 */
class CKEditor5SectionsMedia extends TextFieldEmbedBase {

  /**
   * {@inheritdoc}
   */
  public function parseEntitiesFromText($text) {
    $dom = Html::load($text);
    $xpath = new \DOMXPath($dom);
    $entities = [];
    foreach ($xpath->query('//div[@data-media-uuid]') as $node) {
      $entities[$node->getAttribute('data-media-uuid')] = 'media';
    }
    return $entities;
  }

}
