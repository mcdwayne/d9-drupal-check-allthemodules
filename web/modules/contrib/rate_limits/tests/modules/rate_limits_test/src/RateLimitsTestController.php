<?php

namespace Drupal\rate_limits_test;

use Symfony\Component\HttpFoundation\Response;

class RateLimitsTestController {

  public function handle() {
    return new Response('', 204);
  }

}
