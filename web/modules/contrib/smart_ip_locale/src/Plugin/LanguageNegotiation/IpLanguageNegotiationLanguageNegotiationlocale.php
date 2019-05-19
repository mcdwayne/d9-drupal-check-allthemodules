<?php

namespace Drupal\smart_ip_locale\Plugin\LanguageNegotiation;

use Drupal\language\LanguageNegotiationMethodBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class for identifying language from the IP address.
 *
 * @LanguageNegotiation(
 *   id = Drupal\smart_ip_locale\Plugin\LanguageNegotiation\IpLanguageNegotiationLanguageNegotiationlocale::METHOD_ID,
 *   weight = -1,
 *   name = @Translation("Smart IP country code"),
 *   description = @Translation("Language based on visitor's IP address."),
 *   config_route_name = "language.negotiation_ip"
 * )
 */
class IpLanguageNegotiationLanguageNegotiationlocale extends LanguageNegotiationMethodBase {

  /**
   * The language negotiation method id.
   */
  const METHOD_ID = 'smart-ip-locale-ip';

  /**
   * {@inheritdoc}
   */
  public function getLangcode(Request $request = NULL) {
    $langcode = '';
    if ($request && $this->languageManager) {
      // Disable caching for this page. This only happens when negotiating
      // based on IP. Once the redirect took place to the correct domain
      // or language prefix, this function is not reached anymore and
      // caching works as expected.
      \Drupal::service('page_cache_kill_switch')->trigger();

      $languages = $this->languageManager->getLanguages();
      $countries = \Drupal::config('smart_ip_locale.mappings')->get('map') ?: [];
      $location = \Drupal::service('smart_ip.smart_ip_location');
      $current_country_code = strtolower($location->get('countryCode'));

      if (!empty($current_country_code)) {
        // Check if a language is set for the determined country.
        if (!empty($countries[$current_country_code])) {
          $langcode = $countries[$current_country_code];
        }
      }
    }
    return $langcode;
  }

}
