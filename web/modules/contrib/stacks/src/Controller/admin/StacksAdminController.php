<?php

namespace Drupal\stacks\Controller\admin;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class StacksAdminController.
 * @package Drupal\stacks\Controller\admin
 */
class StacksAdminController extends ControllerBase {

  /**
   * Admin page to manually run the backup.
   */
  public function adminPage() {

    $stacks_content_list_exist = \Drupal::moduleHandler()->moduleExists('stacks_content_list');

    return [
      '#theme' => 'stacks_admin_theme',
      '#stacks_content_list_exist' => $stacks_content_list_exist,
    ];
  }
}
