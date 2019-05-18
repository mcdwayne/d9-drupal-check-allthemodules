<?php

namespace Drupal\mustache_test\Controller;

use Drupal\Component\Utility\Xss;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\mustache\Helpers\MustacheRenderTemplate;

/**
 * Class MustacheTestListController.
 */
class MustacheTestListController {

  /**
   * Returns a list as Json feed.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response.
   */
  public function jsonListFeed(Request $request) {
    $list_data = [];

    $name = Xss::filter($request->get('name', 'NoNameParamGiven'));
    $num_results = (int) $request->get('num', 3);
    if ($num_results < 1) {
      $num_results = 3;
    }

    for ($i = 0; $i < $num_results; $i++) {
      $list_data[] = [
        'name' => $name . ' #' . ($i + 1),
        'value' => uniqid(),
      ];
    }

    $response = new JsonResponse();
    $response->setPrivate();
    $response->setJson(json_encode($list_data));
    return $response;
  }

  /**
   * Returns a page with switch elements for a Mustache template.
   *
   * @return array
   *   A build array for rendering the Mustache template.
   */
  public function pageSwitchables() {
    $render = [];

    $build = MustacheRenderTemplate::build('test_list');
    $build->usingDataFromUrl('/mustache-test/json-list?name=I_Am_Prerendered');
    $build->withClientSynchronization()
      ->usingDataFromUrl('/mustache-test/json-list/?name=Clara')
      ->startsWhenElementWasTriggered('.about-clara')
      ->atEvent('click')
      ->always();
    $build->withClientSynchronization()
      ->usingDataFromUrl('/mustache-test/json-list/?name=John')
      ->startsWhenElementWasTriggered('.about-john')
      ->atEvent('click')
      ->always();
    $render[] = $build->toRenderArray();
    $render[] = ['#markup' => '<div class="about-clara">About Clara (click here)</div>'];
    $render[] = ['#markup' => '<div class="about-john">About John (click here)</div>'];

    return $render;
  }

}
