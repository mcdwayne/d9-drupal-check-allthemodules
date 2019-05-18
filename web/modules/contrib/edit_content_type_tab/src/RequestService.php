<?php
/**
 * @file
 * Contains Drupal\edit_content_type_Tab\RequestService.
 */

namespace Drupal\edit_content_type_tab;

use Symfony\Component\HttpFoundation\Request;

/**
 * A request service that can accept different types of request object.
 * Used so we are not limited to HTTP requests only.
 */
class RequestService {

  protected $request;

  /**
   * Request setter
   * @param Request $request
   */
  public function setRequest(Request $request) {
    $this->request = $request;
  }

  /**
   * Request getter
   * @return Request
   */
  public function getRequest() {
    return $this->request;
  }
}
