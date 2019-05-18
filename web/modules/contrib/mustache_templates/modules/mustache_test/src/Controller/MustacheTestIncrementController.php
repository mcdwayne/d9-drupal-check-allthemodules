<?php

namespace Drupal\mustache_test\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\mustache\Helpers\MustacheRenderTemplate;

/**
 * Class MustacheTestIncrementController.
 */
class MustacheTestIncrementController {

  /**
   * Returns an incrementing list as Json feed.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response.
   */
  public function jsonIncrementingListFeed(Request $request) {
    $page = (int) $request->get('page', 0);
    $data = [
      'info' => 'Your are on page ' . ($page + 1),
      'value' => uniqid(),
    ];

    $response = new JsonResponse();
    $response->setPrivate();
    $response->setJson(json_encode($data));
    return $response;
  }

  /**
   * Returns a page with auto incrementing its content.
   *
   * @return array
   *   A build array for rendering the Mustache template.
   */
  public function pageAutoIncrement() {
    $render = [];

    $build = MustacheRenderTemplate::build('test_auto_increment');
    $build->withPlaceholder(['#markup' => '<b>Loading...</b>']);
    $build->withClientSynchronization()
      ->usingDataFromUrl('/mustache-test/json-increment')
      ->periodicallyRefreshesAt(1000)
      ->increments()
        ->upToNTimes(10);
    $render[] = $build->toRenderArray();

    return $render;
  }

  /**
   * Returns a page with auto incrementing its content interactively.
   *
   * @return array
   *   A build array for rendering the Mustache template.
   */
  public function pageAutoIncrementInteractive() {
    $render = [];

    $build = MustacheRenderTemplate::build('test_auto_increment');
    $build->withPlaceholder(['#markup' => '<b>Awaiting order...</b>']);
    $sync = $build->withClientSynchronization()
      ->executesInnerScripts(TRUE)
      ->startsDelayed(500)
      ->usingDataFromUrl('/mustache-test/json-increment');
    $sync->startsWhenElementWasTriggered('.load-next')
      ->atEvent('click')
      ->always();
    $sync->increments()
        ->upToNTimes(10);
    $render[] = $build->toRenderArray();
    $render[] = ['#markup' => '<div class="load-next">Load next</div>'];

    return $render;
  }

}
