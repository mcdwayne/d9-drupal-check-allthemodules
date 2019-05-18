<?php

namespace Drupal\me_redirect\PathProcessor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Symfony\Component\HttpFoundation\Request;

class MePathProcessor implements InboundPathProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    if (strpos($path, '/me/') === 0) {
      $names = preg_replace('|^\/me\/|', '', $path);
      $names = str_replace('/', ':', $names);

      $path = "/me/$names";
    }

    return $path;
  }
}
