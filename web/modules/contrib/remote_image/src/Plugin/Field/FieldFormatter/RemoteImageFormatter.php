<?php

/**
 * @file
 * Contains Drupal\remote_image\Plugin\Field\FieldFormatter\RemoteImageFormatter.
 */

namespace Drupal\remote_image\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'remote_image' formatter.
 *
 * @FieldFormatter(
 *   id = "remote_image",
 *   label = @Translation("Remote Image"),
 *   field_types = {
 *     "remote_image"
 *   }
 * )
 */
class RemoteImageFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    // Add one image per item.
    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#theme' => 'image',
        '#uri' => $item->uri,
        '#width' => $item->width,
        '#height' => $item->height,
        '#attributes' => ['class' => ['remote-image-item']],
      ];
      // Set the title field.
      if ($this->fieldDefinition->getSetting('title_field') === 1) {
        $elements[$delta]['#title'] = $item->title;
      }
      // Set the alt field.
      if ($this->fieldDefinition->getSetting('alt_field') === 1) {
        $elements[$delta]['#alt'] = $item->title;
      }
    }

    return $elements;
  }
}
