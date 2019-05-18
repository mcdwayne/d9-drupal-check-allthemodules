<?php

namespace Drupal\media_download_all\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\media\Plugin\Field\FieldFormatter\MediaThumbnailFormatter;

/**
 * Plugin implementation of the 'media_download_all_thumbnail' formatter.
 *
 * @FieldFormatter(
 *   id = "media_download_all_thumbnail",
 *   label = @Translation("Thumbnail (MDA)"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class MediaDownloadAllThumbnailFormatter extends MediaThumbnailFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $elements = parent::viewElements($items, $langcode);

    if (!empty($elements)) {
      $field_name = $items->getName();
      $entity = $items->getEntity();
      $entity_type = $entity->getEntityTypeId();
      $entity_id = $entity->id();
      $url = Url::fromUserInput("/media_download_all/$entity_type/$entity_id/$field_name");
      $download_link = Link::fromTextAndUrl('Download All Files', $url)->toRenderable();
      $download_link['#attributes']['class'] = ['media-download-all'];
      $elements[]['download_link'] = $download_link;
    }

    return $elements;
  }

}
