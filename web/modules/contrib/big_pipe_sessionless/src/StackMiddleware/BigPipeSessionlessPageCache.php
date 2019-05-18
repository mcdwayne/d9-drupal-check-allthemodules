<?php

namespace Drupal\big_pipe_sessionless\StackMiddleware;

use Drupal\page_cache\StackMiddleware\PageCache;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @see \Drupal\big_pipe_sessionless\Render\BigPipeSessionless::sendContent
 */
class BigPipeSessionlessPageCache extends PageCache {

  // @codingStandardsIgnoreStart
  public function _storeResponse(Request $request, Response $response) {
    return $this->storeResponse($request, $response);
  }
  // @codingStandardsIgnoreEnd

}
