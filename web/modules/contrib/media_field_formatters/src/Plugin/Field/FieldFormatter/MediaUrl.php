<?php

namespace Drupal\media_field_formatters\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\media\MediaInterface;

/**
 * Plugin implementation of the 'media_url' formatter.
 *
 * @FieldFormatter(
 *   id = "media_url",
 *   label = @Translation("Media object URL"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class MediaUrl extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $media_items = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($media_items)) {
      return $elements;
    }

    /** @var \Drupal\media\MediaInterface[] $media_items */
    foreach ($media_items as $delta => $media) {
      // Only handle media objects.
      if ($media instanceof MediaInterface) {
        $elements[$delta] = [
          // '#markup' => $media->getSource()->getSourceFieldValue($media),
          '#type' => 'inline_template',
          '#template' => '{{ value|raw }}',
          '#context' => [
            'value' => $media->getSource()->getSourceFieldValue($media),
          ],
        ];

        // Add cacheability of each item in the field.
        // $this->renderer->addCacheableDependency($elements[$delta], $media);
      }
    }

    return $elements;
  }

}
