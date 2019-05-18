<?php

namespace Drupal\alipay_api\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for alipay_api module routes.
 */
class AlipayResultController extends ControllerBase {

  /**
   * Main function.
   */
  public function main($method) {
    return _alipay_api_result_callback($method);
  }

}
