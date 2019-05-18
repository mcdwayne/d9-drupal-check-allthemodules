<?php

namespace Drupal\flags_country\Controller;

use Drupal\country\Controller\CountryAutocompleteController;
use Drupal\field\Entity\FieldConfig;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns autocomplete responses for countries.
 */
class CountryFlagAutocompleteController extends CountryAutocompleteController {

  /**
   * Returns response for the country name autocompletion.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object containing the search string.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions for countries.
   */
  public function autocomplete(Request $request, $entity_type, $bundle, $field_name) {
    /** @var \Drupal\Core\Render\Renderer $renderer */
    $renderer = \Drupal::service('renderer');
    $matches = array();
    $string = $request->query->get('q');
    if ($string) {
      $field_definition = FieldConfig::loadByName($entity_type, $bundle, $field_name);
      $countries = \Drupal::service('country.field.manager')->getSelectableCountries($field_definition);
      foreach ($countries as $iso2 => $country) {
        if (strpos(mb_strtolower($country), mb_strtolower($string)) !== FALSE) {
          $label = array(
            'country' => array('#markup' => $country),
            'flag' => array(
              '#theme' => 'flags',
              '#code' => strtolower($iso2),
              '#source' => 'country',
            ),
          );

          $matches[] = array('value' => $country, 'label' => $renderer->render($label));
        }
      }
    }
    return new JsonResponse($matches);
  }

}
