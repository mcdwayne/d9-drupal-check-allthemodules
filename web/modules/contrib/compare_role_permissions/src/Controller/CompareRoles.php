<?php

namespace Drupal\compare_role_permissions\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class CompareRoles.
 *
 * @package Drupal\compare_role_permissions\Controller
 */
class CompareRoles extends ControllerBase {

  /**
   * Diff.
   *
   * @return string
   *   Return Hello string.
   */
  public function diff() {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: diff'),
    ];
  }

}
