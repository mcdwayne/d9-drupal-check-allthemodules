<?php

namespace Drupal\bigcommerce\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class BigCommerceController.
 */
class BigCommerceController extends ControllerBase {

  /**
   * Sets a log filter and redirects to the log.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response object that may be returned by the controller.
   */
  public function showLog() {
    return $this->redirect('dblog.overview', [], ['query' => ['type' => ['bigcommerce.product_sync']]]);
  }

}
