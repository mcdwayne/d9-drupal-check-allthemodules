<?php

namespace Drupal\dialect;

use Drupal\block\Entity\Block;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Block\BlockManager;
use Drupal\Core\Language\LanguageInterface;
use Drupal\dialect\Form\SharedBlockConfigForm;
use Drupal\language\ConfigurableLanguageManager;
use Drupal\Core\Routing\UrlGenerator;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class DialectManager.
 *
 * Checks block instances and provide helpers shared among
 * Block configuration and display, Shared Block ConfigForm.
 *
 * @package Drupal\dialect
 */
class DialectManager {

  // @todo remove unused services and imports
  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Entity\Query\QueryFactory definition.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * Drupal\Core\Block\BlockManager definition.
   *
   * @var \Drupal\Core\Block\BlockManager
   */
  protected $blockManager;

  /**
   * Drupal\language\ConfigurableLanguageManager definition.
   *
   * @var \Drupal\language\ConfigurableLanguageManager
   */
  protected $languageManager;

  /**
   * Drupal\Core\Routing\UrlGenerator definition.
   *
   * @var \Drupal\Core\Routing\UrlGenerator
   */
  protected $urlGenerator;

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configurationFactory;

  /**
   * Immutable configuration shared form a global configuration form.
   *
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  private $sharedBlockConfiguration;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   Entity Type Manager definition.
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   Entity Query definition.
   * @param \Drupal\Core\Block\BlockManager $block_manager
   *   Block Manager definition.
   * @param \Drupal\language\ConfigurableLanguageManager $language_manager
   *   Language Manager definition.
   * @param \Drupal\Core\Routing\UrlGenerator $url_generator
   *   Url Generator definition.
   * @param \Drupal\Core\Config\ConfigFactory $configuration_factory
   *   Configuration Factory definition.
   */
  public function __construct(EntityTypeManager $entity_type_manager, QueryFactory $entity_query, BlockManager $block_manager, ConfigurableLanguageManager $language_manager, UrlGenerator $url_generator, ConfigFactory $configuration_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityQuery = $entity_query;
    $this->blockManager = $block_manager;
    $this->languageManager = $language_manager;
    $this->urlGenerator = $url_generator;
    $this->configurationFactory = $configuration_factory;

    $this->sharedBlockConfiguration = $this->configurationFactory->get('dialect.shared_block_config');
  }

  /**
   * Get excluded languages if any.
   *
   * @return array
   *   Array of excluded languages ids.
   */
  public function getExcludedLanguageIds() {
    $result = [];
    if (is_array($this->sharedBlockConfiguration->get(SharedBlockConfigForm::EXCLUDED_LANGUAGES))) {
      $result = $this->sharedBlockConfiguration->get(SharedBlockConfigForm::EXCLUDED_LANGUAGES);
    }
    return $result;
  }

  /**
   * Redirects to the front page in the site default language.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The front page redirect response.
   */
  public function getFrontPageRedirectResponse() {
    $currentLanguage = $this->languageManager->getCurrentLanguage();
    $defaultLanguage = $this->languageManager->getDefaultLanguage();
    if ($currentLanguage->getId() === $defaultLanguage->getId()) {
      throw new \Exception(t('The default site language cannot be redirected.'));
    }
    $url = $this->urlGenerator->generateFromRoute('<front>', [], ['language' => $defaultLanguage]);
    return new RedirectResponse($url, 301);
  }

  /**
   * Returns the fallback node redirect response.
   *
   * @return null|\Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect Response.
   *
   * @throws \Exception
   */
  public function getFallbackNodeRedirectResponse() {
    $result = NULL;
    $nodeId = $this->sharedBlockConfiguration->get(SharedBlockConfigForm::FALLBACK_NODE);
    if ($nodeId !== NULL) {
      $url = $this->urlGenerator->generateFromRoute('entity.node.canonical', ['node' => (int) $nodeId]);
      $result = new RedirectResponse($url, 301);
    }
    else {
      // @todo specialize
      throw new \Exception();
    }
    return $result;
  }

  /**
   * Checks if the current node is the fallback one.
   *
   * @return bool
   *   Is the current node used for the language fallback.
   */
  public function isCurrentNodeFallback() {
    $result = FALSE;
    $node = \Drupal::routeMatch()->getParameter('node');
    if ($node instanceof Node) {
      $currentNodeId = $node->id();
      $fallbackNodeId = $this->sharedBlockConfiguration->get(SharedBlockConfigForm::FALLBACK_NODE);
      if ((int) $currentNodeId === (int) $fallbackNodeId) {
        $result = TRUE;
      }
    }
    return $result;
  }

  /**
   * Checks if a fallback node exists for a given language.
   *
   * @param string $languageId
   *   Language ID.
   *
   * @return bool
   *   Fallback node exists.
   */
  public function fallbackNodeExists($languageId) {
    $result = FALSE;
    if ($this->sharedBlockConfiguration->get(SharedBlockConfigForm::FALLBACK_NODE) !== NULL) {
      // Load the fallback node.
      $nodeId = $this->sharedBlockConfiguration->get(SharedBlockConfigForm::FALLBACK_NODE);
      $node = $this->entityTypeManager->getStorage('node')->load((int) $nodeId);
      // Check if the translation exists.
      $translations = $node->getTranslationLanguages();
      if (array_key_exists($languageId, $translations)) {
        $result = TRUE;
      }
    }
    return $result;
  }

  /**
   * Gets the current language id.
   *
   * @return string
   *   Current language id.
   */
  public function getCurrentLanguageId() {
    return $this->languageManager->getCurrentLanguage()->getId();
  }

  /**
   * Checks if a language is in the fallback language list.
   *
   * @return bool
   *   Is a fallback language.
   */
  public function isFallbackLanguage($languageId) {
    $result = FALSE;

    // If no fallback is set, quit.
    $hasNodeFallback = $this->sharedBlockConfiguration->get('single_node_fallback');
    if ((int) $hasNodeFallback === 0) {
      return $result;
    }

    // At least one fallback language must be defined.
    // @todo review this test
    $fallbackLanguages = $this->sharedBlockConfiguration->get(SharedBlockConfigForm::FALLBACK_LANGUAGES);
    if (empty($languageId) || empty($fallbackLanguages)) {
      return $result;
    }
    if (is_string($fallbackLanguages) && $fallbackLanguages == LanguageInterface::LANGCODE_NOT_SPECIFIED) {
      return $result;
    }

    // Finally check if the language is one of these.
    if (in_array($languageId, $fallbackLanguages)) {
      $result = TRUE;
    }
    return $result;
  }

  /**
   * Determines if a page must be redirected to the fallback node.
   *
   * @todo change function name, ambiguous.
   *
   * @return bool
   *   Must redirect.
   */
  public function isRedirectPage() {
    static $page_match;
    // Cache visibility result if function is called more than once.
    if (!isset($page_match)) {
      $redirect_request_path_pages = $this->sharedBlockConfiguration->get('redirect.request_path_pages');
      // Match path if necessary.
      if (!empty($redirect_request_path_pages)) {
        $pages = Unicode::strtolower($redirect_request_path_pages);
        $redirect_request_path_mode = $this->sharedBlockConfiguration->get('redirect.request_path_mode');
        // Compare the lowercase path alias (if any) and internal path.
        $path = \Drupal::service('path.current')->getPath();
        $path_alias = Unicode::strtolower(\Drupal::service('path.alias_manager')
          ->getAliasByPath($path));
        $page_match = \Drupal::service('path.matcher')
          ->matchPath($path_alias, $pages) || (($path != $path_alias) && \Drupal::service('path.matcher')
            ->matchPath($path, $pages));
        // When $redirect_request_path_mode has a value of 0, the redirect
        // is done on all pages except those listed in $pages. When
        // set to 1, it is done only on those pages listed in $pages.
        $page_match = !($redirect_request_path_mode xor $page_match);
      }
      else {
        $page_match = TRUE;
      }
    }
    return $page_match;
  }

  /**
   * Returns a list of unavailable translations.
   *
   * @return array|string
   *   List of unavailable translations.
   */
  public function getUnavailableTranslations() {
    // @todo implement
    return [];
  }

  /**
   * Returns an array of Dialect Block Plugin instance ids.
   *
   * @return array|int
   *   List of ids.
   */
  public function getBlockInstanceIds() {
    // @todo use getDefinition to avoid string comparison
    $ids = $this->entityQuery->get('block')
    // ->getPluginDefinition()['provider']
      ->condition('plugin', 'dialect_block:language_interface')->execute();
    return $ids;
  }

  /**
   * Returns if a Dialect Block Plugin has at least one instance.
   *
   * @return bool
   *   Dialect Block instance exists.
   */
  public function hasBlockInstance() {
    return !empty($this->getBlockInstanceIds());
  }

  /**
   * Returns the number of Dialect Block Plugin instances.
   *
   * @return int
   *   Number of Dialect Block instances.
   */
  public function countBlockInstances() {
    return count($this->getBlockInstanceIds());
  }

  /**
   * Returns all the instances from a Block.
   *
   * @return array
   *   List of Dialect Blocks.
   */
  private function getBlockInstances() {
    $result = [];
    $ids = $this->getBlockInstanceIds();
    if (!empty($ids)) {
      $blocks = $this->entityTypeManager->getStorage('block')
        ->loadMultiple($ids);
      foreach ($blocks as $key => $block) {
        $result[$key] = $block;
      }
    }
    return $result;
  }

  /**
   * Returns the settings for a Block.
   *
   * @param \Drupal\block\Entity\Block $block
   *   Block.
   *
   * @return mixed|null
   *   Block settings.
   */
  private function getBlockSettings(Block $block) {
    $settings = $block->get('settings');
    return $settings;
  }

  /**
   * Returns an array of all the Dialect Block settings.
   *
   * @return array
   *   Array of Dialect Block instances settings.
   */
  public function getBlockSettingsFromInstances() {
    $result = [];
    $instances = $this->getBlockInstances();
    foreach ($instances as $block) {
      $result[] = $this->getBlockSettings($block);
    }
    return $result;
  }

  /**
   * Displays a warning if the fallback node has no translation.
   *
   * If any of the languages defined has no translation for the
   * fallback node, display a warning to the user if it has the
   * permissions to change the block configuration or translate the node
   * (in other words, one of the two way to address this).
   */
  public function unavailableFallbackTranslationsWarning() {
    // @todo implement
  }

}
