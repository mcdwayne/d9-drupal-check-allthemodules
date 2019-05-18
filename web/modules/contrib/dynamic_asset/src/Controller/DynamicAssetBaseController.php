<?php

namespace Drupal\dynamic_asset\Controller;

use Symfony\Component\HttpFoundation\Response;

class DynamicAssetBaseController {

  /**
   * @param $data array
   *
   * @return Response
   */
  protected function css($data) {
    $content = "";
    foreach ($data as $selectors => $styles) {
      $content .= $selectors . '{';
      foreach ($styles as $key => $value) {
        $content .= sprintf('%s: %s;', $key, $value);
      }
      $content .= '}';
    }

    $response = new Response($content);
    $response->headers->set('content-type','text/css');
    return $response;
  }

  /**
   * @param $content string
   *
   * @return Response
   */
  protected function js($content) {
    $response = new Response($content);
    $response->headers->set('content-type','application/javascript');
    return $response;
  }
}