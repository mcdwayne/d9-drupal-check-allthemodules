<?php
/**
 * @file
 * Contains \Drupal\xwechat_menu\Controller\MenuConfigController.
 */

namespace Drupal\xwechat_menu\Controller;

use Drupal\Core\Controller\ControllerBase;

class MenuConfigController extends ControllerBase {
  public function content() {
    return array(
        '#type' => 'markup',
        '#markup' => $this->t('Hello, World!'),
    );
  }
}

