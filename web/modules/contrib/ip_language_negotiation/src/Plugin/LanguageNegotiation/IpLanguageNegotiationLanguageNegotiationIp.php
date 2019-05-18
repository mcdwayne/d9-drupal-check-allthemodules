<?php

namespace Drupal\ip_language_negotiation\Plugin\LanguageNegotiation;

use Drupal\language\LanguageNegotiationMethodBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class for identifying language from the IP address.
 *
 * @LanguageNegotiation(
 *   id = Drupal\ip_language_negotiation\Plugin\LanguageNegotiation\IpLanguageNegotiationLanguageNegotiationIp::METHOD_ID,
 *   weight = -1,
 *   name = @Translation("IP address"),
 *   description = @Translation("Language based on visitor's IP address."),
 *   config_route_name = "ip_language_negotiation.form"
 * )
 */
class IpLanguageNegotiationLanguageNegotiationIp extends LanguageNegotiationMethodBase {

  /**
   * The language negotiation method id.
   */
  const METHOD_ID = 'ip-language-negotiation-ip';

  /**
   * {@inheritdoc}
   */
  public function getLangcode(Request $request = NULL) {
    $langcode = NULL;

    if ($request && $this->languageManager) {
      // Disable caching for this page. This only happens when negotiating
      // based on IP. Once the redirect took place to the correct domain
      // or language prefix, this function is not reached anymore and
      // caching works as expected.
      \Drupal::service('page_cache_kill_switch')->trigger();

      $languages = $this->languageManager->getLanguages();
      $countries = $this->config->get('ip_language_negotiation.settings')->get('ip_language_negotiation_countries') ?: array();
      $current_ip = \Drupal::request()->getClientIp();

      // Check for debug settings. If enabled, use it.
      if (\Drupal::config('ip2country.settings')->get('debug')) {
        // Debug Country entered.
        if (\Drupal::config('ip2country.settings')->get('test_type') == 0) {
          $country_code = \Drupal::config('ip2country.settings')->get('test_country') ?: 'US';
        }
        // Debug IP entered.
        else {
          $ip = \Drupal::config('ip2country.settings')->get('test_ip_address') ?: $current_ip;
          $country_code = ip2country_get_country($ip);
        }
      }

      // Check if the country code can be determined by the IP.
      else {
        $country_code = ip2country_get_country($current_ip);
      }

      if (!empty($country_code)) {
        // Check if a language is set for the determined country.
        if (!empty($countries[$country_code])) {
          $langcode = $countries[$country_code];
        }
      }
    }

    return $langcode;
  }
}


