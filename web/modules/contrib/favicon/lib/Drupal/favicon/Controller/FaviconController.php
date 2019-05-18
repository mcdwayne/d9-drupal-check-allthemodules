<?php

/**
 * @file
 * Contains \Drupal\favicon\Controller\FaviconController.
 */

namespace Drupal\favicon\Controller;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * Favicon page controller.
 */
class FaviconController {

  /**
   * Generates an favicon page.
   */
  public function content() {
    // Add favicon.
    if (theme_get_setting('features.favicon')) {
      $favicon = theme_get_setting('favicon.url');
      $type = theme_get_setting('favicon.mimetype');

      $response = new Response();
      $request = \Drupal::request();

      $response->headers->set('Content-Type', $type);
      $response->headers->set('Expires', 0);
      $response->setContent(file_get_contents($favicon));

      $response->prepare($request);
      $response->send();
      // We are done.
      exit;
    }
    throw new NotFoundHttpException();
  }
}
