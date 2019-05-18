<?php

namespace Drupal\brightcove_proxy\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dummy page for proxy testing.
 */
class BrightcoveProxyTest extends ControllerBase {

  /**
   * Returns an empty response for testing the proxy.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Empty response.
   */
  public function testPage() {
    return new Response();
  }

}
