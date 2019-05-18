<?php
/**
 * @file
 * Contains \Drupal\easy_currency_con\Controller\AutocompleteController.
 */

namespace Drupal\easy_currency_con\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for displaying a list of countries.
 */
class AutocompleteController {

  /**
   * List of countries.
   */
  public function countryList() {
    $currency_list = array(
      'USD' => 'USD (America)',
      'JPY' => 'JPY (Japan)',
      'INR' => 'INR (India)',
      'CNY' => 'CNY (China)',
      'EUR' => 'EUR (Euro)',
      'BGN' => 'BGN (Bulgaria)',
      'CZK' => 'CZK (Czech Republic)',
      'DKK' => 'DKK (Denmark)',
      'GBP' => 'GBP (Great Britain - UK)',
      'HUF' => 'HUF (Hungary)',
      'LTL' => 'LTL (Lithuania)',
      'PLN' => 'PLN (Poland)',
      'RON' => 'RON (Romania)',
      'SEK' => 'SEK (Sweden)',
      'CHF' => 'CHF (Switzerland)',
      'NOK' => 'NOK (Bouvet Island)',
      'HRK' => 'HRK (Croatia)',
      'RUB' => 'RUB (Russian Federation)',
      'TRY' => 'TRY (Turkey)',
      'AUD' => 'AUD (Australia)',
      'BRL' => 'BRL (Brazil)',
      'CAD' => 'CAD (Canada)',
      'HKD' => 'HKD (Hong Kong)',
      'IDR' => 'IDR (Indonesia)',
      'ILS' => 'ILS (Israel)',
      'KRW' => 'KRW (South Korea)',
      'MXN' => 'MXN (Mexico)',
      'MYR' => 'MYR (Malaysia)',
      'NZD' => 'NZD (New Zealand)',
      'PHP' => 'PHP (Philippines)',
      'SGD' => 'SGD (Singapore)',
      'THB' => 'THB (Thailand)',
      'ZAR' => 'ZAR (South Africa)',
      'ISK' => 'ISK (Iceland)',
    );
    return $currency_list;
  }

  /**
   * Retrieves suggestions for country list autocompletion.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing autocomplete suggestions.
   */
  public function countryAutocomplete(Request $request) {
    $typed_category = $request->query->get('q');
    $matches = array();
    $currency_list = $this->countryList();
    foreach ($currency_list as $key => $string) {
      if (stripos($string, $typed_category) !== FALSE) {
        $matches[] = array('value' => $key, 'label' => $string);
      }
    }
    return new JsonResponse($matches);
  }

}
