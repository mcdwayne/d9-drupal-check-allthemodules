<?php

/**
 * @file
 * Contains \Drupal\image_browser\Plugin\views\field.
 */

namespace Drupal\image_browser\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Defines a custom field that renders a preview of a file, for the purposes of.
 *
 * @ViewsField("image_browser_preview")
 */
class ImageBrowserPreview extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    /** @var \Drupal\file\Entity\File $file */
    $file = $values->_entity;
    if($file->getMimeType() == 'image/svg+xml'){
      return $build = [
        '#markup' => '<img src="' . file_create_url($file->getFileUri()) .'"/>',
      ];
    }
    return $build = [
      '#theme' => 'image_style',
      '#style_name' => 'image_browser_thumbnail',
      '#uri' => $file->getFileUri(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {}

  /**
   * {@inheritdoc}
   */
  public function clickSortable() { return FALSE; }

}
