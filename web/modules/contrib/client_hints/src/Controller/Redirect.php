<?php

namespace Drupal\client_hints\Controller;

use Drupal\Core\Cache\CacheableRedirectResponse;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Redirect controller.
 */
class Redirect extends ControllerBase {

  /**
   * Image url redirect.
   */
  public function image(Request $request) {

    $scheme = file_default_scheme();

    $target = $request->query->get('file');
    $image_uri = $scheme . '://' . $target;

    // Get request parameters.
    $file = $request->get('file');
    $dpr = $request->get('dpr') ? (int) $request->get('dpr') : 1;
    $width = (int) $request->get('width');

    // Get corresponding image style redirect url.
    $image_style_url = \Drupal::service('client_hints')->getImageRedirectUrl($file, $dpr, $width);

    // Add cacheability metadata.
    $response = new CacheableRedirectResponse($image_style_url);
    $response->getCacheableMetadata()->addCacheContexts(['url.query_args:file', 'url.query_args:dpr', 'url.query_args:width']);

    return $response;

  }

}
