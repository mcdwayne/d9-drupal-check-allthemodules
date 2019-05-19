<?php
/**
 * @file
 * Contains \Drupal\xwechat_user\Controller\UserListController.
 */

namespace Drupal\xwechat_user\Controller;

use Drupal\Core\Controller\ControllerBase;

class UserListController extends ControllerBase {
  public function content() {
    return array(
        '#type' => 'markup',
        '#markup' => $this->t('Hello, World!'),
    );
  }
}

