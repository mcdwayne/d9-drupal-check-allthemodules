<?php

/**
 * @file
 * Contains \Drupal\md_fontello\Controller\MDFontello.
 */

namespace Drupal\md_fontello\Controller;

use Drupal\Core\Controller\ControllerBase;

class MDFontello extends ControllerBase {

  public function viewFont($font) {
    $icons = $this->entityTypeManager()->getStorage('md_fontello')->load($font);
    $classes = unserialize($icons->classes);
    return [
      '#theme' => 'md_icon_list',
      '#attached' => [
        'library' => [
          'md_fontello/md_fontello.' . $font
        ],
      ],
      '#name' => $font,
      '#icons' => $classes
    ];
  }

}