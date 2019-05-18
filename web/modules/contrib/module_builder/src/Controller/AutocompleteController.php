<?php

namespace Drupal\module_builder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Utility\Tags;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\NestedArray;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a route controller for properties with extra options.
 */
class AutocompleteController extends ControllerBase {

  /**
   * Handler for autocomplete request for properties with extra options.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param string $property_address
   *   The address of the property this autocomplete request is for, as a string
   *   imploded with ':'.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The matching options.
   */
  public function handleAutocomplete(Request $request, $property_address) {
    $results = [];

    // Get the typed string from the URL, if it exists.
    if ($input = $request->query->get('q')) {
      $property_address = explode(':', $property_address);

      // Get the component data info.
      try {
        // TODO: inject.
        $generate_task = \Drupal::service('module_builder.drupal_code_builder')->getTask('Generate', 'module');
      }
      catch (\Exception $e) {
        // If we get here we should be ok.
      }

      // Get the property info for the property this autocomplete is for.
      $component_data_info = $generate_task->getRootComponentDataInfo();
      $property_info = NestedArray::getValue($component_data_info, $property_address);

      $extra_options_keys = array_keys($property_info['options_extra']);

      $matched_keys = preg_grep("@{$input}@", $extra_options_keys);

      foreach ($matched_keys as $key) {
        $results[] = [
          'value' => $key,
          'label' => $key, // $property_info['options_extra'][$key],
        ];
      }
    }

    return new JsonResponse($results);
  }

}
