<?php

namespace Drupal\backports\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;

/**
 * Plugin implementation of the 'image_url' formatter.
 *
 * @FieldFormatter(
 *   id = "image_url",
 *   label = @Translation("URL to image"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class ImageUrlFormatter extends ImageFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    /** @var \Drupal\file\Entity\File[] $images */
    $images = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($images)) {
      return $elements;
    }

    $image_link_setting = $this->getSetting('image_link');
    // Url to be linked to.
    $link_url = FALSE;
    // Check if the formatter involves a link.
    if ($image_link_setting == 'content') {
      $entity = $items->getEntity();
      if (!$entity->isNew()) {
        $link_url = $entity->urlInfo();
      }
    }

    /** @var \Drupal\image\Entity\ImageStyle|false $image_style */
    $image_style = ($image_style_setting = $this->getSetting('image_style')) && !empty($image_style_setting) ? $this->imageStyleStorage->load($image_style_setting) : NULL;

    foreach ($images as $delta => $image) {
      /** @var \Drupal\file\Entity\File $image */
      $image_uri = $image->getFileUri();
      $url = $image_style
        ? $image_style->buildUrl($image_uri)
        : file_create_url($image_uri);

      // Set the link url if settings require such.
      $link_url = ($image_link_setting == 'file') ? Url::fromUri($url) : $link_url;

      // Add cacheable metadata from the image and image style.
      $cacheable_metadata = CacheableMetadata::createFromObject($image);
      if ($image_style) {
        $cacheable_metadata = $cacheable_metadata->merge(CacheableMetadata::createFromObject($image_style));
      }

      // Add a link if we have a valid link url.
      if ($link_url instanceof Url) {
        $elements[$delta] = Link::fromTextAndUrl($url, $link_url)
          ->toRenderable();
      }
      else {
        $elements[$delta] = ['#markup' => $url];
      }
      $cacheable_metadata->applyTo($elements[$delta]);
    }

    return $elements;
  }
}
