<?php

namespace Drupal\kalastatic\PathProcessor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Symfony\Component\HttpFoundation\Request;

class KalastaticPathProcessor implements InboundPathProcessorInterface {

  public function processInbound($path, Request $request) {
    // Hands off, this is our path!
    if (strpos($path, '/kalastatic/') === 0) {
      $sub_paths = array(
        'prototype',
        'styleguide',
      );
      foreach ($sub_paths as $sub_path) {
        if (strpos($path, "/kalastatic/$sub_path") === 0) {
          // Split apart everything that comes after /kalastatic/$sub_path and
          // turn it into a colon seperated string so that our menu routes will
          // get fired. We will have to piece the url back together again in
          // KalastaticServer controller so we can find the file that was
          // actually being requested.
          $args = preg_replace("|^\/kalastatic\/$sub_path\/|", '', $path);
          $args = str_replace('/',':', $args);
          $path = "/kalastatic/$sub_path/$args";
        }
      }
    }
    return $path;
  }
}
