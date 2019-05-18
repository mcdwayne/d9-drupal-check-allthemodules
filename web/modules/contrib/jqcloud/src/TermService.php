<?php

namespace Drupal\jqcloud;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Cache\DatabaseBackend;
use Drupal\taxonomy\VocabularyInterface;

/**
 * Class TermService.
 */
class TermService implements TermServiceInterface {

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;

  /**
   * Drupal\Core\Language\LanguageManager definition.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Cache\DatabaseBackend definition.
   *
   * @var \Drupal\Core\Cache\DatabaseBackend
   */
  protected $cacheData;

  /**
   * TagService constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection.
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   *   Language manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Cache\DatabaseBackend $cache_data
   *   Cache data service.
   */
  public function __construct(
    Connection $database,
    LanguageManager $language_manager,
    EntityTypeManagerInterface $entity_type_manager,
    DatabaseBackend $cache_data
  ) {
    $this->database = $database;
    $this->languageManager = $language_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->cacheData = $cache_data;
  }

  /**
   * Returns cache tag.
   *
   * @param \Drupal\taxonomy\VocabularyInterface $vocabulary
   *   Taxonomy vocabulary.
   *
   * @return string
   *   Cache tag.
   */
  public function getCacheTags(VocabularyInterface $vocabulary) {
    return [
      "jqcloud:{$vocabulary->id()}",
      'taxonomy_term_list',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTerms(
    VocabularyInterface $vocabulary,
    $size = self::DEFAULT_SIZE) {

    // Define cache name.
    $language = $this->languageManager->getCurrentLanguage();
    $cache_name = 'jqcloud_' . $language->getId() . '_' . $size;

    // Load data from cache.
    $cache = $this->cacheData->get($cache_name);
    $terms = [];

    // Make sure cache has data.
    if (!empty($cache->data)) {
      $terms = $cache->data;
    }
    else {
      $query = $this->entityTypeManager->getStorage('taxonomy_term')
        ->getQuery();
      $query->condition('vid', $vocabulary->id());
      $query->condition('langcode', $language->getId());
      $query->sort('weight');

      if (isset($size)) {
        $query->range(0, $size);
      }

      $tids = $query->execute();
      $terms = $this->entityTypeManager->getStorage('taxonomy_term')
        ->loadMultiple($tids);

      // Set data to cache.
      $this->cacheData->set(
        $cache_name,
        $terms,
        Cache::PERMANENT,
        $this->getCacheTags($vocabulary)
      );
    }

    return $terms;
  }

}
