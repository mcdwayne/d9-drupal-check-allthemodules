<?php

namespace Drupal\mustache_test\Controller;

use Drupal\mustache\Helpers\MustacheRenderTemplate;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * A controller delivering resources for testing Mustache templats.
 */
class MustacheTestController {

  /**
   * Returns a Json response containing dummy data.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response.
   */
  public function jsonFeed() {
    $data = [
      'foo' => 'bar',
      'id' => uniqid(),
      'number' => rand(),
      'nested' => ['key' => rand()],
    ];
    $response = new JsonResponse();
    $response->setPrivate();
    $response->setJson(json_encode($data));
    return $response;
  }

  /**
   * Returns a plain page for testing a certain template with period.
   *
   * @return array
   *   A build array for rendering the Mustache template.
   */
  public function pagePeriod() {
    $build = MustacheRenderTemplate::build('some_test_template');
    $build->usingDataFromUrl('/mustache-test/json');
    $build->withClientSynchronization()
      ->startsDelayed(500)
      ->periodicallyRefreshesAt(1000);
    return $build->toRenderArray();
  }

  /**
   * Returns a plain page for interactively testing a certain template.
   *
   * @return array
   *   A build array for rendering the Mustache template.
   */
  public function pageInteractive() {
    $build = MustacheRenderTemplate::build('some_test_template');
    $build->usingDataFromUrl('/mustache-test/json');
    $build->withClientSynchronization()
      ->startsWhenElementWasTriggered('.click-me')
      ->atEvent('click')
      ->always();

    $render = [
      $build->toRenderArray(),
      [
        '#type' => 'markup',
        '#markup' => '<div class="click-me">Click here to refresh!</div>'
      ]
    ];
    return $render;
  }

  /**
   * Returns a plain page for interactively appending a certain template.
   *
   * @return array
   *   A build array for rendering the Mustache template.
   */
  public function pageInteractiveAdjacent() {
    $build = MustacheRenderTemplate::build('some_test_template');
    $build->usingDataFromUrl('/mustache-test/json');
    $build->withClientSynchronization()
      ->insertsAt('afterbegin')
      ->executesInnerScripts(TRUE)
      ->startsDelayed(200)
      ->startsWhenElementWasTriggered('.click-me')
      ->atEvent('click')
      ->always();

    $render = [
      $build->toRenderArray(),
      [
        '#type' => 'markup',
        '#markup' => '<div class="click-me">Click here to add another one!</div>'
      ]
    ];
    return $render;
  }

  /**
   * Returns a plain page for testing a certain template with a triggering placeholder.
   *
   * @return array
   *   A build array for rendering the Mustache template.
   */
  public function pageTriggerPlaceholder() {
    $build = MustacheRenderTemplate::build('some_test_template');
    $placeholder = ['#markup' => '<div class="click-me">Click me!</div>'];
    $build->withPlaceholder($placeholder);
    $build->usingDataFromUrl('/mustache-test/json');
    $build->withClientSynchronization()
      ->periodicallyRefreshesAt(1000)
      ->startsWhenElementWasTriggered('.click-me')
        ->atEvent('click')
        ->once();
    return $build->toRenderArray();
  }

  /**
   * Returns a plain page for testing a certain template with a limited triggering placeholder.
   *
   * @return array
   *   A build array for rendering the Mustache template.
   */
  public function pageLimitedTriggerPlaceholder() {
    $build = MustacheRenderTemplate::build('some_test_template');
    $placeholder = ['#markup' => '<div class="click-me">Click me!</div>'];
    $build->withPlaceholder($placeholder);
    $build->usingDataFromUrl('/mustache-test/json');
    $build->withClientSynchronization()
      ->periodicallyRefreshesAt(1000)
      ->upToNTimes(3)
      ->startsWhenElementWasTriggered('.click-me')
        ->atEvent('click')
        ->once();
    return $build->toRenderArray();
  }

}
