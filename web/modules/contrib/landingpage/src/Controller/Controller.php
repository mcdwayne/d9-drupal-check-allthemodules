<?php

namespace Drupal\landingpage\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Unicode;

/**
 * Class Controller.
 *
 * @package Drupal\landingpage\Controller
 */
class Controller extends ControllerBase {

  /**
   * Returns response for the country name autocompletion.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object containing the search string.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions for countries.
   */
  public function autocomplete(Request $request) {
    $matches = array();
    $string = $request->query->get('q');
    if ($string) {
      $options = array();

      $classes = \Drupal::service('entity.manager')->getStorage('landingpage_skin')->loadMultiple();

      foreach ($classes as $key => $class) {
        if (strpos(Unicode::strtolower($class->label()), Unicode::strtolower($string)) !== FALSE) {
          $matches[] = array('value' => $class->label() . ' [' . $key . ']', 'label' => $class->label());
        }
      }
    }
    return new JsonResponse($matches);
  }

}
