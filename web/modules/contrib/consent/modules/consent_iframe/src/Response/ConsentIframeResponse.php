<?php

namespace Drupal\consent_iframe\Response;

use Drupal\Core\Render\HtmlResponse;

/**
 * Class ConsentIframeResponse.
 *
 * @internal
 */
final class ConsentIframeResponse extends HtmlResponse {

  /**
   * {@inheritdoc}
   */
  public function __construct($content = '', $status = 200, $headers = []) {
    parent::__construct($content, $status, $headers);
    $this->headers = new ConsentIframeResponseHeaderBag($headers);
  }

}
