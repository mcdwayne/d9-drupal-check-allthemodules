<?php

namespace Drupal\alipay_api\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for alipay_api module routes.
 */
class AlipayGatewayController extends ControllerBase {

  /**
   * Main function.
   */
  public function main() {
    return alipay_api_page();
  }

}
