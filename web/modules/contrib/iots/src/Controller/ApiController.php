<?php

namespace Drupal\iots\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class ApiController.
 */
class ApiController extends ControllerBase {

  /**
   * Get.
   *
   * @return string
   *   Return Hello string.
   */
  public function get() {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: get')
    ];
  }

}
