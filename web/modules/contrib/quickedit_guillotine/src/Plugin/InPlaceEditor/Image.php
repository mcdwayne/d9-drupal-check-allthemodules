<?php

namespace Drupal\quickedit_guillotine\Plugin\InPlaceEditor;

use \Drupal\image\Plugin\InPlaceEditor\Image as ImageBase;

/**
 * Defines the image text in-place editor.
 *
 * @InPlaceEditor(
 *   id = "image"
 * )
 */
class Image extends ImageBase {

  /**
   * {@inheritdoc}
   */
  public function getAttachments() {
    return [
      'library' => [
        'quickedit_guillotine/quickedit.inPlaceEditor.image',
      ],
    ];
  }

}
