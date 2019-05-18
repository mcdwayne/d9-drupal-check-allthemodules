<?php

namespace Drupal\reporting;

use Symfony\Component\HttpFoundation\Response;

/**
 * Class ReportingResponse.
 *
 * @package Drupal\reporting
 */
class ReportingResponse extends Response {

  /**
   * Create a new Reporting Response.
   *
   * Reporting responses have no body content.
   *
   * @param int $status
   *   The response status code.
   * @param array $headers
   *   An array of response headers.
   */
  public function __construct($status = 200, array $headers = []) {
    parent::__construct('', $status, $headers);
  }

}
