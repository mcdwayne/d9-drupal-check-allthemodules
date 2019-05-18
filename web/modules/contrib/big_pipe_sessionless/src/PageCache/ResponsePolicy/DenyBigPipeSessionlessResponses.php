<?php

namespace Drupal\big_pipe_sessionless\PageCache\ResponsePolicy;

use Drupal\big_pipe\Render\BigPipeResponse;
use Drupal\Core\PageCache\ResponsePolicyInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A policy denying caching of a BigPipe sessionless responses.
 */
class DenyBigPipeSessionlessResponses implements ResponsePolicyInterface {

  /**
   * {@inheritdoc}
   */
  public function check(Response $response, Request $request) {
    if ($response instanceof BigPipeResponse) {
      return static::DENY;
    }
  }

}
