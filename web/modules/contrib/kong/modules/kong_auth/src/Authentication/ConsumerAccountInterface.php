<?php

namespace Drupal\kong_auth\Authentication;

use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Interface ConsumerAccountInterface.
 */
interface ConsumerAccountInterface extends AccountInterface {

  /**
   * Sets the request object.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   */
  public function setRequest(Request $request);

}
