<?php

namespace Drupal\twig_extender_tests\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class TestController.
 */
class TestController extends ControllerBase {

  /**
   * Test.
   *
   * @return string
   *   Return Hello string.
   */
  public function test() {

    $nodes = \Drupal::entityManager()->getStorage('node')->loadMultiple();
    $build = [
      '#type' => 'container',
      '#childrends' => [],
    ];
    foreach ($nodes as $node) {
      $build['#children'][] = [
        '#theme' => 'twig_extender_test_node',
        '#node' => $node,
      ];
      $build['#children'][] = [
        '#theme' => 'twig_extender_test_node',
        '#node' => $node->toUrl(),
      ];
    }
    return $build;
  }

}
