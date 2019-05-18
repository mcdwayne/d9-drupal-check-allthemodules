<?php

namespace Drupal\country_state_field\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Tags;
use Drupal\Component\Utility\Unicode;

/**
 * Defines a route controller for entity autocomplete form elements.
 */
class AutoCompleteController extends ControllerBase {

  /**
   * Handler for autocomplete request.
   */
  public function handleAutocomplete(Request $request) {
    $results = [];

    // Get the typed string from the URL, if it exists.
    if ($input = $request->query->get('q')) {
      $typed_string = Tags::explode($input);
      $typed_string = Unicode::strtolower(array_pop($typed_string));

      $results = $this->getValues($typed_string);
    }

    return new JsonResponse($results);
  }

  /**
   * {@inheritdoc}
   */
  public function getValues($search) {
    if ($search) {
      $query = \Drupal::entityQuery('city')
        // ->condition('name', $name)
        ->Condition('name', $search . '%', 'like')
        ->sort('name', 'asc');

      $ids = $query->execute();

      $cities = entity_load_multiple('city', $ids);

      foreach ($cities as $city) {
        if ($city) {
          $state = $city->getState();
          $country = $state->getCountry();

          $results[] = [
            'value' => $country->getName() . ' (' . $country->id() . '),' . $state->getName() . ' (' . $state->id() . '),' . $city->getName() . ' (' . $city->id() . ')',
            'label' => $country->getName() . ' - ' . $state->getName() . ' - ' . $city->getName(),
          ];
        }
      }

      return $results;
    }
  }

}
