<?php

namespace Drupal\yandexdisk;

use Drupal\Component\Utility\Html;

/**
 * Exception subclass to use in work with YandexDiskApiWebdav.
 */
class YandexDiskException extends \Exception {

  /**
   * Result of a request made prior to exception was thrown.
   *
   * @var mixed
   */
  protected $response;

  /**
   * Constructs the exception.
   *
   * @param string $message
   *   (optional) The Exception message to throw. Overrides any message in
   *   service response.
   */
  public function __construct($message = NULL) {
    $response = YandexDiskApiWebdav::$lastResponse;
    $this->response = $response;

    // Get message from last service response if it isn't set explicitly.
    if (!isset($message) && isset($response)) {
      $message = $response->getStatusCode() . ':' . $response->getReasonPhrase();

      $message = Html::escape($message);
    }

    parent::__construct($message, $response ? $response->getStatusCode() : 0);
  }

  /**
   * Returns last service response.
   *
   * @return mixed
   *   Last service response.
   */
  public function getServiceResponse() {
    return $this->response;
  }

}
