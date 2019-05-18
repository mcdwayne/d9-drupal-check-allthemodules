<?php

namespace Drupal\just_giving\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\just_giving\JustGivingSearch;
use Drupal\just_giving\JustGivingClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a route controller for autocomplete charity search elements.
 */
class JustGivingCharitySearchController extends ControllerBase {

  /**
   * Drupal\just_giving\JustGivingClient definition.
   *
   * @var \Drupal\just_giving\JustGivingClient
   */
  protected $justGivingClient;

  /**
   * Drupal\just_giving\JustGivingSearch definition.
   *
   * @var \Drupal\just_giving\JustGivingSearch
   */
  protected $justGivingSearch;


  /**
   * Constructs a new SearchController object.
   */
  public function __construct() {
    $this->justGivingClient = new JustGivingClient();
    $this->justGivingSearch = new JustGivingSearch($this->justGivingClient);
  }

  /**
   * Handler for autocomplete request.
   *
   * @param Request $request
   *  Request object.
   * @param $field_name
   *  Field name passed.
   * @param integer $count
   *  Number of items returned.
   *
   * @return JsonResponse
   */
  public function handleAutocomplete(Request $request, $field_name, $count) {
    $results = [];

    // Get the typed string from the URL, if it exists.
    if ($input = $request->query->get('q')) {

      $jgSearchResults = $this->justGivingSearch->charitySearch($input, $count);
      if (!empty($jgSearchResults->charitySearchResults)) {
        foreach ($jgSearchResults->charitySearchResults as $item) {
          $results[] = [
            'value' => $item->charityId,
            'label' => $item->charityDisplayName . ' (' . $item->charityId . ')',
          ];
        }
      }
    }

    return new JsonResponse($results);
  }

}
