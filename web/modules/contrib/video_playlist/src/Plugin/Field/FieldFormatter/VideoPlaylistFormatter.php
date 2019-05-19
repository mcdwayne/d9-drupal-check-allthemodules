<?php

namespace Drupal\video_playlist\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'Random_default' formatter.
 *
 * @FieldFormatter(
 *   id = "video_playlist_default",
 *   label = @Translation("Video Playlist"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */

class VideoPlaylistFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */

  public function settingsSummary() {
    $summary = array();
    $settings = $this->getSettings();
    $summary[] = t('Creates a simple video playlist.');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */

  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element[0] = array(
      '#theme' => 'video_file_display',
    );
    foreach ($items as $delta => $item) {
      $element[0]['#items'][] = array(
        'uri' => file_create_url($item->entity->get('uri')->value),
        'filename' => $item->entity->get('filename')->value,
          'description' => $item->description,
      );
    }
    return $element;
  }
}
