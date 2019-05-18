<?php

namespace Drupal\cb\Controller;

use Drupal\Component\Utility\Tags;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Provides route responses for cb module.
 */
class CbController extends ControllerBase {

  /**
   * Returns a form to add a new chained breadcrumb.
   *
   * @return array
   *   The cb breadcrumb add form.
   */
  public function addForm() {
    $breadcrumb = $this->entityManager()->getStorage('cb_breadcrumb')->create([]);
    return $this->entityFormBuilder()->getForm($breadcrumb);
  }

  /**
   * The autocomplete suggestions for cb module.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request of the page.
   */
  public function autocomplete(Request $request) {
    $matches = [];

    if ($input = $request->query->get('q')) {
      $string = Tags::explode($input);
      $string = array_pop($string);

      $query = db_select('router', 'r')
        ->fields('r', ['path'])
        ->condition('r.path', '%' . $string . '%', 'LIKE')
        ->range(0, 10);

      $result = $query->execute();
      foreach ($result as $match) {
        //$row = preg_replace('#\{\w+\}#', '%', $match->path);
        $row = $match->path;
        $matches[] = $match->path;
      }
    }

    return new JsonResponse($matches);
  }

}
