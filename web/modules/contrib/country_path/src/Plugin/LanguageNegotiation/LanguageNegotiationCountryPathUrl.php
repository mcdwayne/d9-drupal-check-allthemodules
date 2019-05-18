<?php

namespace Drupal\country_path\Plugin\LanguageNegotiation;

use Symfony\Component\HttpFoundation\Request;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;

/**
 * Class for identifying language via URL prefix or domain.
 *
 * @LanguageNegotiation(
 *   id = \Drupal\country_path\Plugin\LanguageNegotiation\LanguageNegotiationCountryPathUrl::METHOD_ID,
 *   types = {\Drupal\Core\Language\LanguageInterface::TYPE_INTERFACE,
 *   \Drupal\Core\Language\LanguageInterface::TYPE_CONTENT,
 *   \Drupal\Core\Language\LanguageInterface::TYPE_URL},
 *   weight = -8,
 *   name = @Translation("Country Path Language Handler URL"),
 *   description = @Translation("Country Path Language Handler from the URL (Path prefix and domain)."),
 *   config_route_name = "language.negotiation_url"
 * )
 */
class LanguageNegotiationCountryPathUrl extends LanguageNegotiationUrl {

  /**
   * The language negotiation method id.
   */
  const METHOD_ID = 'country-path-language-url';

  /**
   * URL language negotiation: use the path prefix as URL language indicator.
   */
  const CONFIG_PATH_PREFIX = 'path_prefix';

  /**
   * URL language negotiation: use the domain as URL language indicator.
   */
  const CONFIG_DOMAIN = 'domain';

  /**
   * {@inheritdoc}
   */
  public function getLangcode(Request $request = NULL) {
    $langcode = NULL;
    if ($request && $this->languageManager) {
      $languages = $this->languageManager->getLanguages();
      $config = $this->config->get('language.negotiation')->get('url');

      switch ($config['source']) {
        case LanguageNegotiationCountryPathUrl::CONFIG_PATH_PREFIX:
          $request_path = urldecode(trim($request->getPathInfo(), '/'));
          $path_args = explode('/', $request_path);

          $prefix = array_shift($path_args);
          $prefix2 = array_shift($path_args);

          // Search prefix within added languages.
          $negotiated_language = FALSE;
          foreach ($languages as $language) {
            if (isset($config['prefixes'][$language->getId()]) && $config['prefixes'][$language->getId()] == $prefix) {
              $negotiated_language = $language;
              break;
            }
            elseif (isset($config['prefixes'][$language->getId()]) && $config['prefixes'][$language->getId()] == $prefix2) {
              $negotiated_language = $language;
              break;
            }
          }

          if ($negotiated_language) {
            $langcode = $negotiated_language->getId();
          }
          break;

        case LanguageNegotiationCountryPathUrl::CONFIG_DOMAIN:
          // Get only the host, not the port.
          $http_host = $request->getHost();
          foreach ($languages as $language) {
            // Skip the check if the language doesn't have a domain.
            if (!empty($config['domains'][$language->getId()])) {
              // Ensure that there is exactly one protocol in the URL when
              // checking the hostname.
              $host = 'http://' . str_replace(['http://', 'https://'], '', $config['domains'][$language->getId()]);
              $host = parse_url($host, PHP_URL_HOST);
              if ($http_host == $host) {
                $langcode = $language->getId();
                break;
              }
            }
          }
          break;
      }
    }
    return $langcode;
  }

}
