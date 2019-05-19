<?php
/**
 * @file
 * Contains \Drupal\xwechat_media\Controller\MediaListController.
 */

namespace Drupal\xwechat_media\Controller;

use Drupal\Core\Controller\ControllerBase;

class MediaListController extends ControllerBase {
  public function content() {
    return array(
        '#type' => 'markup',
        '#markup' => $this->t('Hello, World!'),
    );
  }
}

