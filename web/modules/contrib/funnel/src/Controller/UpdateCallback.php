<?php

namespace Drupal\funnel\Controller;

/**
 * @file
 * Contains \Drupal\funnel\Controller\Page.
 */

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller routines for page example routes.
 */
class UpdateCallback extends ControllerBase {

  /**
   * Update Node.
   */
  public function node($vid, $tid = 0) {
    $json = "";
    if (is_numeric($tid)) {
      $vocabs = Helpers::vocabs();
      $list = [];
      if ($vocabs) {
        foreach ($vocabs as $vid => $vocabulary) {
          $url = Url::fromRoute('funnel.vocab', ['vid' => $vid]);
          $name = $vocabulary->get('name');
          $list[] = \Drupal::l($name, $url);
        }
      }
      $json = json_encode($_POST, JSON_UNESCAPED_UNICODE);
    }
    $response = new Response($json);
    $response->headers->set('Content-Type', 'application/json');
    return $response;
  }

}
