<?php

/**
 * @file
 * Contains
 *   \Drupal\remote_image\Plugin\Field\FieldFormatter\RemoteImageWithMetadataFormatter.
 */

namespace Drupal\remote_image\Plugin\Field\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * 'remote_image' formatter which also displays the metadata like width/height.
 *
 * @FieldFormatter(
 *   id = "remote_image_metadata",
 *   label = @Translation("Remote Image with metadata"),
 *   field_types = {
 *     "remote_image"
 *   }
 * )
 */
class RemoteImageWithMetadataFormatter extends RemoteImageFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    foreach ($items as $delta => $item) {
      $image = $elements[$delta];

      $element = [
        'image' => [
          '#prefix' => '<div class="field__label">' . $this->t('Image') . '</div>',
          $image,
        ],
        'width' => [
          '#prefix' => '<div class="field__label">' . $this->t('Width') . '</div>',
          '#markup' => $image['#width'],
        ],
        'height' => [
          '#prefix' => '<div class="field__label">' . $this->t('Height') . '</div>',
          '#markup' => $image['#height'],
        ],
        'title' => [
          '#prefix' => '<div class="field__label">' . $this->t('Title') . '</div>',
          '#markup' => $image['#title'],
        ],
      ];

      if (isset($image['#alt'])) {
        $element['alt'] = [
          '#prefix' => '<div class="field__label">' . $this->t('Alt') . '</div>',
          '#markup' => $image['#alt'],
        ];
      }
      $elements[$delta] = $element;
    }

    return $elements;
  }

}
