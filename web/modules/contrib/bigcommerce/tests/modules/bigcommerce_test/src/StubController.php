<?php

namespace Drupal\bigcommerce_test;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class StubController
 *
 * @package Drupal\bigcommerce_test
 */
class StubController extends ControllerBase {

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param $folder
   * @param $part1
   * @param $part2
   * @param $part3
   * @param $part4
   * @param $part5
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function get(Request $request, $folder, $part1, $part2, $part3, $part4, $part5) {
    $method = $request->getMethod() === 'GET' ? NULL : $request->getMethod();
    $parts = array_filter([$part1, $part2, $part3, $part4, $part5, $method]);

    $filename = realpath(__DIR__ . '/..') . '/stubs/' . $folder . '/' . implode('_', $parts) . '.json';
    if (!file_exists($filename)) {
      // Throw a more helpful exception.
      throw new \RuntimeException(sprintf("Can not find stub file: %s", $filename));
    }
    // Use include so files can contain logic using PHP.
    ob_start();
    @include $filename;
    $data = json_decode(ob_get_clean(), TRUE);
    if (!$data) {
      throw new \RuntimeException(sprintf("Invalid JSON in stub file: %s", $filename));
    }
    return new JsonResponse($data, $data['status'] ?? 200);
  }

}
