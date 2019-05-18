<?php

namespace Drupal\health_check_url\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for route that returns health check url type.
 */
class HealthCheckController extends ControllerBase {


  protected $string;

  protected $type;

  /**
   * Constructs a new HealthCheckController object.
   */
  public function __construct() {

    $config = $this->config('health_check_url.settings');
    $this->string = $config->get('string') != "" ? $config->get('string') : "Passed";
    $this->type = $config->get('type') != "" ? $config->get('type') : "timestamp";
  }

  /**
   * Timestamp health check.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Response.
   */
  public function healthCheckUrl() {
    $options = [
      "timestamp" => time(),
      "string" => $this->string,
      "stringWithTimestamp" => $this->string.' - ' . time(),
      "stringWithDateTime" => $this->string.' ' . strftime("at %T on %D"),
      "stringWithDateTimestamp" => $this->string.' ' . strftime("at %T on %D") . ' (' . time() . ')',
    ];
    return $this->buildResponse($options[$this->type]);
  }

  /**
   * Build response.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Response.
   */
  public function buildResponse($string) {
    $response = new Response();
    $response->headers->set('Content-Type', 'text/plain');
    $response->setContent($string);
    return $response;
  }

}
