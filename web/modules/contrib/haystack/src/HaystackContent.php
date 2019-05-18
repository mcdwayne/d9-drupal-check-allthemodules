<?php

namespace Drupal\haystack;

use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;

/**
 * Class HaystackContent.
 */
class HaystackContent {

  /**
   * Drupal\haystack\HaystackCore definition.
   *
   * @var \Drupal\haystack\HaystackCore
   */
  protected $haystackCore;

  protected $languageManager;

  /**
   * HaystackContent constructor.
   *
   * @param \Drupal\haystack\HaystackCore $haystack_core
   *   Haystack core service.
   * @param \Drupal\Core\Language\LanguageManager $languageManager
   *   Language Manager service.
   */
  public function __construct(HaystackCore $haystack_core, LanguageManager $languageManager) {
    $this->haystackCore = $haystack_core;
    $this->languageManager = $languageManager;
  }

  /**
   * Prepare the node for Haystack indexing.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to be indexed.
   */
  public function indexNode(NodeInterface $node) {

    if ($this->allowNodeIndexing($node)) {

      // Check if the node has additional translations.
      $nodeTranslations = $node->getTranslationLanguages(FALSE);

      // Only one language.
      if (!count($nodeTranslations)) {
        $defaultNodeData = $this->createNodePackage($node);
        $this->saveData($defaultNodeData);
      }
      else {
        // With multiple languages, make sure that we process the default first.
        $defaultLanguage = $this->languageManager->getDefaultLanguage()->getId();
        $defaultNodeData = $this->createNodePackage($node->getTranslation($defaultLanguage));
        $this->saveData($defaultNodeData);

        $additionalLanguages = $this->haystackCore->getSetting('languages');
        foreach ($nodeTranslations as $lang_id => $translation) {
          if (in_array($lang_id, $additionalLanguages)) {
            $translatedNode = $node->getTranslation($lang_id);
            $translateNodeData = $this->createNodePackage($translatedNode);
            $this->saveData($translateNodeData);
          }
        }
      }
    }
  }

  /**
   * Create the data package for Haystack.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node from which to create the search package.
   *
   * @return array
   *   The package of data to be send to Haystack.
   */
  public function createNodePackage(NodeInterface $node) {
    $isDefault = TRUE;
    $hasLanguage = TRUE;
    $defaultLanguage = $this->languageManager->getDefaultLanguage()->getId();
    $languageCode = $node->language()->getId();

    // If the node language is undefined use the default language code.
    if ($languageCode == 'und' || $languageCode == 'zxx') {
      $languageCode = $defaultLanguage;
      $hasLanguage = FALSE;
    }

    // If the node language is other then default, add the extension to the ID.
    if ($languageCode != $defaultLanguage) {
      $isDefault = FALSE;
    }

    $node_types = $this->haystackCore->getContentTypes(TRUE);

    // Clean out tags and spaces from the body text.
    $nodeView = node_view($node, 'search_index', $languageCode);
    $renderNode = \Drupal::service('renderer')->render($nodeView);
    $spaceString = str_replace('<', ' <', $renderNode);
    $doubleSpace = strip_tags($spaceString);
    $singleSpace = preg_replace('/\s+/', ' ', $doubleSpace);

    $package = [
      'api_token' => $this->haystackCore->getSetting('api_key'),
      'id' => 'content-' . $node->id() . (!$isDefault ? '-' . $languageCode : ''),
      'es_type' => $node->getType(),
      'type' => '<type data-type="' . $node->getType() . '">' . $node_types[$node->getType()] . '</type>',
      'title' => $hasLanguage ? $node->getTranslation($languageCode)->getTitle() : $node->getTitle(),
      'link' => Url::fromRoute('entity.node.canonical', ['node' => $node->id()], ['language' => $node->language()])
        ->setAbsolute()
        ->toString(),
      'menu' => $this->haystackCore->getMenuTitle($node->id(), $languageCode),
      'body' => $singleSpace,
      'image' => $this->haystackCore->getImage($node),
      'tags' => $this->haystackCore->getTags($node->id()),
      'publishedAt' => date('Y-m-d', $node->getCreatedTime()),
      'lang' => $languageCode,
    ];

    // Allow custom modules to override $package or add new fields.
    $protected_fields = [
      'api_token',
      'id',
      'es_type',
      'title',
      'link',
      'menu',
      'body',
      'tags',
      'lang',
    ];
    $modules = \Drupal::moduleHandler()
      ->getImplementations('haystack_get_fields');
    foreach ($modules as $module) {
      $fields = \Drupal::moduleHandler()
        ->invoke($module, 'haystack_get_fields', [$node]);
      foreach ($fields as $name => $value) {
        if (!empty($value) && !in_array($name, $protected_fields)) {
          $package[$name] = $value;
        }
      }
    }

    return $package;
  }

  /**
   * Determine if the node can be indexed.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to check.
   *
   * @return bool
   *   Bool if the node can be indexed.
   */
  public function allowNodeIndexing(NodeInterface $node) {

    $modules = \Drupal::moduleHandler()
      ->getImplementations('haystack_allow_indexing');
    foreach ($modules as $module) {
      if (FALSE === \Drupal::moduleHandler()->invoke($module, 'haystack_allow_indexing', [$node])) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Save the data to Haystack.
   *
   * @param array $package
   *   The data to be saved.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  private function saveData(array $package) {
    $this->haystackCore->apiCall($package);
    $this->haystackCore->incrementMeterPos();
  }

  /**
   * Delete call for nodes.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to be deleted.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function deleteNode(NodeInterface $node) {

    // Only process if the node is of the  types selected in configuration.
    if (in_array($node->getType(), $this->haystackCore->getContentTypes())) {

      $package = [
        'api_token' => $this->haystackCore->getSetting('api_key'),
        'id' => 'content-' . $node->id(),
        'type' => $node->getType(),
      ];
      // Write changes to the Server.
      $this->haystackCore->apiCall($package, 'index', 'delete');

      // Iterate through translations.
      $nodeTranslations = $node->getTranslationLanguages(FALSE);
      $additionalLanguages = $this->haystackCore->getSetting('languages');
      foreach ($nodeTranslations as $lang_id => $translation) {
        if (in_array($lang_id, $additionalLanguages)) {
          $package['id'] = 'content-' . $node->id() . '-' . $lang_id;
          $this->haystackCore->apiCall($package, 'index', 'delete');
        }
      }

    }
  }

}
