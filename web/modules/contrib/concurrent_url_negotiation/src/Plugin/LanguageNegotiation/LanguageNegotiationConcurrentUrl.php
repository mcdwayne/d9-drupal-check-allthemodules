<?php

namespace Drupal\concurrent_url_negotiation\Plugin\LanguageNegotiation;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Drupal\concurrent_url_negotiation\ConcurrentUrlNegotiationConfig;
use Symfony\Component\HttpFoundation\Request;

/**
 * Plugin for identifying language via concurrent URL (domain and prefix).
 *
 * @LanguageNegotiation(
 *   id = \Drupal\concurrent_url_negotiation\Plugin\LanguageNegotiation\LanguageNegotiationConcurrentUrl::METHOD_ID,
 *   types = {
 *    \Drupal\Core\Language\LanguageInterface::TYPE_INTERFACE,
 *    \Drupal\Core\Language\LanguageInterface::TYPE_CONTENT,
 *    \Drupal\Core\Language\LanguageInterface::TYPE_URL
 *   },
 *   weight = -9,
 *   name = @Translation("Concurrent URL"),
 *   description = @Translation("Language from the URL (Path and domain). !!Replaces core URL negotiator."),
 *   config_route_name = "concurrent_url_negotiation.config_form"
 * )
 */
class LanguageNegotiationConcurrentUrl extends LanguageNegotiationUrl {

  /**
   * The language negotiation method id.
   */
  const METHOD_ID = 'language-concurrent-url';

  /**
   * {@inheritdoc}
   */
  public function getLangcode(Request $request = NULL) {
    // If a language was detected from the request, then return the lang-code.
    $detectedLanguage = $this->getLanguageFrom($request);
    if ($detectedLanguage) {
      return $detectedLanguage['language']->getId();
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    $detectLanguage = $this->getLanguageFrom($request, $path);

    // If detected a language from and it included a prefix, then chop it off.
    if ($detectLanguage && !empty($detectLanguage['prefix_match'])) {
      $path = preg_replace('/\/[^\/]+\/?/', '/', $path, 1);
    }

    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = array(), Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    list($urlScheme, $port) = $request ?
      [$request->getScheme(), $request->getPort()] : ['http', 80];

    $languages = array_flip(array_keys($this->languageManager->getLanguages()));
    $currentLanguage = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_URL);

    // Language can be passed as an option, or we go for current URL language.
    if (!isset($options['language'])) {
      $language_url = $currentLanguage;
      $options['language'] = $language_url;
    }
    // We allow only added languages here.
    elseif (!is_object($options['language']) || !isset($languages[$options['language']->getId()])) {
      return $path;
    }

    $config = $this->getConfig();
    if (is_object($options['language']) && isset($config[$options['language']->getId()])) {
      $targetNegotiation = $config[$options['language']->getId()];

      // If the language we are navigating to has a non-empty prefix add it.
      if (!empty($prefix = $this->getBestPrefix($targetNegotiation['prefixes']))) {
        $options['prefix'] = $prefix . '/';
        if ($bubbleable_metadata) {
          $bubbleable_metadata->addCacheContexts(['languages:' . LanguageInterface::TYPE_URL]);
        }
      }

      // If the target language is in a another domain, then make the link
      // absolute.
      if (
        $request &&
        $options['language']->getId() != $currentLanguage->getId() &&
        $targetNegotiation['domain'] != ConcurrentUrlNegotiationConfig::DOMAIN_ANY &&
        $targetNegotiation['domain'] != $request->getHost()
      ) {
        // Save the original base URL. If it contains a port, we need to
        // retain it below.
        if (!empty($options['base_url'])) {
          // The colon in the URL scheme messes up the port checking below.
          $normalized_base_url = str_replace(array('https://', 'http://'), '', $options['base_url']);
        }

        // Ask for an absolute URL with our modified base URL.
        $options['absolute'] = TRUE;
        $options['base_url'] = $urlScheme . '://' . $targetNegotiation['domain'];

        // In case either the original base URL or the HTTP host contains a
        // port, retain it.
        if (isset($normalized_base_url) && strpos($normalized_base_url, ':') !== FALSE) {
          list(, $port) = explode(':', $normalized_base_url);
          $options['base_url'] .= ':' . $port;
        }
        elseif (($urlScheme == 'http' && $port != 80) || ($urlScheme == 'https' && $port != 443)) {
          $options['base_url'] .= ':' . $port;
        }

        if (isset($options['https'])) {
          if ($options['https'] === TRUE) {
            $options['base_url'] = str_replace('http://', 'https://', $options['base_url']);
          }
          elseif ($options['https'] === FALSE) {
            $options['base_url'] = str_replace('https://', 'http://', $options['base_url']);
          }
        }

        // Add Drupal's sub-folder from the base_path if there is one.
        $options['base_url'] .= rtrim(base_path(), '/');
        if ($bubbleable_metadata) {
          $bubbleable_metadata->addCacheContexts(['languages:' . LanguageInterface::TYPE_URL, 'url.site']);
        }

      }
    }
    return $path;
  }

  /**
   * Get the language from the request.
   *
   * It matches first on domain then on path if the latter was even configured.
   *
   * @param null|\Symfony\Component\HttpFoundation\Request $request
   *    The request from which to get language.
   * @param null|string $path
   *    (Optional) If provided it will be used rather than the one in request.
   *
   * @return array|null
   *    Returns Array[
   *      language: The language object.
   *      domain_match: The matched domain.
   *      path_match: The prefix matched if any.
   *    ] or NULL if nothing was matched.
   */
  protected function getLanguageFrom(Request $request = NULL, $path = NULL) {
    if ($request && $this->languageManager) {
      $domain = $request->getHost();
      list($prefix) = explode('/', trim($path ?: $request->getPathInfo(), '/'));

      // It is very important to prioritize between the configurations.
      // Given that there is a domain placeholder we want to match first on
      // explicit domains. Also we match on non-empty paths first.
      $candidatesPerDomain = $this->getDomainMatchingConfig();
      $availableLanguages = $this->languageManager->getLanguages();

      $pathCandidates = [];
      // First add specific domain candidates.
      if (array_key_exists($domain, $candidatesPerDomain)) {
        $pathCandidates = $candidatesPerDomain[$domain];
      }

      // Then add the ones matching for all domains so they have less priority.
      if (array_key_exists(ConcurrentUrlNegotiationConfig::DOMAIN_ANY, $candidatesPerDomain)) {
        $pathCandidates = array_merge(
          $pathCandidates, $candidatesPerDomain[ConcurrentUrlNegotiationConfig::DOMAIN_ANY]);
      }

      foreach ($pathCandidates as $candidate) {
        if (!array_key_exists($candidate['langcode'], $availableLanguages)) {
          continue;
        }

        if (empty($candidate['prefix']) || $candidate['prefix'] == $prefix) {
          return [
            'language' => $availableLanguages[$candidate['langcode']],
            'domain_match' => $domain,
            'prefix_match' => $candidate['prefix'],
          ];
        }
      }
    }

    return NULL;
  }

  /**
   * Gets the configuration of this plugin.
   *
   * @return mixed
   *    The configuration.
   */
  protected function getConfig() {
    if (!isset($this->configuration)) {
      $this->configuration = $this->config
        ->get('concurrent_url_negotiation.config')
        ->get('url_negotiations') ?: [];
    }

    return $this->configuration;
  }

  /**
   * Gets the configuration keyed by domain and sorted by prefix.
   *
   * This is a helper method to speed up language negotiation.
   *
   * @return array
   *    Array of domain => prefixes.
   */
  protected function getDomainMatchingConfig() {
    if (!isset($this->domainMatching)) {
      $domainMatching = [];
      // Separate all prefixes so they can be easily sorted.
      foreach ($this->getConfig() as $langcode => $config) {
        foreach ($config['prefixes'] as $prefix) {
          $domainMatching[$config['domain']][] = [
            'prefix' => $prefix,
            'langcode' => $langcode,
          ];
        }
      }

      // Make sure that the empty prefix is always the last one, as it will
      // match for any request path.
      foreach ($domainMatching as &$matching) {
        uasort($matching, function ($a) {
          return $a['prefix'] == '';
        });
      }

      $this->domainMatching = $domainMatching;
    }

    return $this->domainMatching;
  }

  /**
   * Gets the best fit prefix from a set of prefixes.
   *
   * @param array $prefixes
   *    Set of prefixes.
   *
   * @return string
   *    The best fit prefix.
   */
  protected function getBestPrefix(array $prefixes) {
    // The empty prefix should be preferred if available.
    if (($empty = array_search('', $prefixes)) !== FALSE) {
      return $empty;
    }

    // Otherwise choose the first one.
    return $prefixes[0];
  }

}
