<?php

/**
 * @file
 * Contains \Drupal\monitoring\Controller\CategoryAutocompleteController.
 */

namespace Drupal\monitoring\Controller;

use Drupal\Component\Render\HtmlEscapedText;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns auto complete responses for sensor categories.
 */
class CategoryAutocompleteController {

  /**
   * Retrieves suggestions for sensor category auto completion.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing autocomplete suggestions.
   */
  public function autocomplete(Request $request) {
    // Stores the sensor categories.
    $configs = monitoring_sensor_manager()->getAllSensorConfig();
    $categories = array();
    foreach ($configs as $conf) {
      $category = $conf->category;
      if (!in_array($category, $categories)) {
        $categories[] = $category;
      }
    }
    $matches = array();
    // Create the pattern to search in categories.
    $pattern = '/^' . $request->query->get('q') . '/i';
    $prefixMatches = preg_grep($pattern,  $categories);
    foreach ($prefixMatches as $config) {
      $matches[] = array('value' => $config, 'label' => new HtmlEscapedText($config)
      );
    }
    return new JsonResponse($matches);
  }
}
