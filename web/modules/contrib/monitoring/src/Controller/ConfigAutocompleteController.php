<?php

/**
 * @file
 * Contains \Drupal\monitoring\Controller\ConfigAutocompleteController.
 */

namespace Drupal\monitoring\Controller;

use Drupal\Component\Render\HtmlEscapedText;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns auto complete responses for config.
 */
class ConfigAutocompleteController {

  /**
   * Retrieves suggestions for config auto completion.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing autocomplete suggestions.
   */
  public function autocomplete(Request $request) {
    $matches = array();
    $prefixMatches = array_slice(\Drupal::service('config.factory')->listAll($request->query->get('q')), 0, 10);
    foreach ($prefixMatches as $config) {
      $matches[] = array('value' => $config, 'label' => new HtmlEscapedText($config));
    }
    return new JsonResponse($matches);
  }

}
