<?php

namespace Drupal\single_language_url_prefix;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class for identifying language via URL prefix when there is single language.
 *
 * @see \Drupal\Core\PathProcessor\PathProcessorAlias
 * @see \Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl
 */
class SingleLanguageNegotiationUrl implements InboundPathProcessorInterface, OutboundPathProcessorInterface {

  /**
   * An alias manager for looking up the system path.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * Excluded paths from config.
   *
   * @var null|string
   */
  protected $excludedPaths = NULL;

  /**
   * Constructs a SingleLanguageNegotiationUrl object.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   An alias manager for looking up the system path.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The configuration factory.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   Path Matcher service.
   */
  public function __construct(LanguageManagerInterface $language_manager,
                              ConfigFactoryInterface $config,
                              PathMatcherInterface $path_matcher) {
    $this->languageManager = $language_manager;
    $this->config = $config;
    $this->pathMatcher = $path_matcher;
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    $languages = $this->languageManager->getLanguages();

    // We don't do anything if more then one language enabled.
    // That works by default.
    if (count ($languages) > 1 || $this->isPathExcluded($path)) {
      return $path;
    }

    $config = $this->config->get('language.negotiation')->get('url');
    $source = $config['source'] ?? '';
    if ($source === LanguageNegotiationUrl::CONFIG_PATH_PREFIX) {
      $parts = explode('/', trim($path, '/'));
      $prefix = array_shift($parts);

      $language = reset($languages);
      if (isset($config['prefixes'][$language->getId()]) && $config['prefixes'][$language->getId()] == $prefix) {
        // Rebuild $path with the language removed.
        $path = '/' . implode('/', $parts);
      }
    }

    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [],
                                  Request $request = NULL,
                                  BubbleableMetadata $bubbleable_metadata = NULL) {
    $languages = $this->languageManager->getLanguages();

    // We don't do anything if more then one language enabled.
    // That works by default.
    if (count($languages) > 1 || $this->isPathExcluded($path)) {
      return $path;
    }

    $config = $this->config->get('language.negotiation')->get('url');
    $source = $config['source'] ?? '';
    if ($source === LanguageNegotiationUrl::CONFIG_PATH_PREFIX) {
      $language = reset($languages);

      if (isset($config['prefixes'][$language->getId()])) {
        $options['prefix'] = $config['prefixes'][$language->getId()] . '/';
      }
    }

    return $path;
  }

  /**
   * Helper function to check if current path is excluded or not.
   *
   * @param $path
   *   Path to check.
   *
   * @return bool
   *   TRUE if path is excluded.
   */
  protected function isPathExcluded($path) {
    if (!isset($this->excludedPaths)) {
      $config = $this->config->get('single_language_url_prefix.settings');
      $this->excludedPaths = $config->get('excluded_paths') ?? '';
    }

    if (empty($this->excludedPaths)) {
      return FALSE;
    }

    return (bool) $this->pathMatcher->matchPath($path, $this->excludedPaths);
  }

}
