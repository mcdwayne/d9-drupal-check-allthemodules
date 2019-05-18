<?php

namespace Drupal\search_api_synonym\Import;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\search_api_synonym\Entity\Synonym;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Importer class.
 *
 * Process and import synonyms data.
 *
 * @package Drupal\search_api_synonym
 */
class Importer {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The database connection used to check the IP against.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs Importer.
   */
  public function __construct() {
    $this->entityManager = \Drupal::service('entity.manager');
    $this->entityRepository = \Drupal::service('entity.repository');
    $this->moduleHandler = \Drupal::service('module_handler');
    $this->connection = \Drupal::service('database');
  }

  /**
   * Execute the import of an array with synonyms.
   *
   * @param array $items
   *   Raw synonyms data.
   * @param array $settings
   *   Import settings.
   *
   * @return array
   *   Array with info about the result.
   */
  public function execute(array $items, array $settings) {
    // Prepare items.
    $items = $this->prepare($items, $settings);

    // Create synonyms.
    $results = $this->createSynonyms($items, $settings);

    return $results;
  }

  /**
   * Prepare and validate the data.
   *
   * @param array $items
   *   Raw synonyms data.
   * @param array $settings
   *   Import settings.
   *
   * @return array
   *   Array with prepared data.
   */
  private function prepare(array $items, array $settings) {
    $prepared = [];

    foreach ($items as $item) {
      // Decide which synonym type to use.
      if ($settings['synonym_type'] != 'mixed') {
        $type = $settings['synonym_type'];
      }
      else {
        $type = !empty($item['type']) ? $item['type'] : 'empty';
      }

      $prepared[$type][$item['word']][] = $item['synonym'];
    }

    return $prepared;
  }

  /**
   * Create synonyms.
   *
   * @param array $items
   *   Raw synonyms data.
   * @param array $settings
   *   Import settings.
   *
   * @return array
   *   Array with info about the result.
   */
  public function createSynonyms(array $items, array $settings) {
    $context = [];

    // Import with batch.
    $operations = [];

    foreach ($items as $type => $item) {
      // Continue with next item if type is not valid.
      if ($type == 'empty') {
        $context['results']['errors'][] = [
          'word' => key($item),
          'synonyms' => current($item)
        ];
        continue;
      }

      // Add each item to the batch.
      foreach ($item as $word => $synonyms) {
        $operations[] = [
          '\Drupal\search_api_synonym\Import\Importer::createSynonym',
          [$word, $synonyms, $type, $settings]
        ];
      }
    }

    $batch = [
      'title' => t('Import synonyms...'),
      'operations' => $operations,
      'finished' => '\Drupal\search_api_synonym\Import\Importer::createSynonymBatchFinishedCallback',
    ];
    batch_set($batch);

    return isset($context['results']) ? $context['results'] : NULL;
  }

  /**
   * Create / update a synonym.
   *
   * @param string $word
   *   The source word we add the synonym for.
   * @param array $synonyms
   *   Simple array with synonyms.
   * @param string $type
   *   The synonym type.
   * @param array $settings
   *   Import settings.
   * @param array $context
   *   Batch context - also used for storing results in non batch operations.
   */
  public static function createSynonym($word, array $synonyms, $type, array $settings, array &$context) {
    $request_time = \Drupal::time()->getRequestTime();

    // Check if we have an existing synonym entity we should update.
    $sid = Importer::lookUpSynonym($word, $type, $settings['langcode']);

    // Trim spaces from synonyms.
    $synonyms = array_map('trim', $synonyms);

    // Load and update existing synonym entity.
    if ($sid) {
      $entity = Synonym::load($sid);

      // Update method = Merge.
      if ($settings['update_existing'] == 'merge') {
        $existing = $entity->getSynonyms();
        $existing = array_map('trim', explode(',', $existing));
        $synonyms = array_unique(array_merge($existing, $synonyms));
      }

      $synonyms_str = implode(',', $synonyms);
      $entity->setSynonyms($synonyms_str);
    }
    // Create new entity.
    else {
      $entity = Synonym::create([
        'langcode' => $settings['langcode'],
      ]);
      $uid = \Drupal::currentUser()->id();
      $entity->setOwnerId($uid);
      $entity->setCreatedTime($request_time);
      $entity->setType($type);
      $entity->setWord($word);
      $synonyms_str = implode(',', $synonyms);
      $entity->setSynonyms($synonyms_str);
    }

    $entity->setChangedTime($request_time);
    $entity->setActive($settings['status']);

    $entity->save();

    if ($sid = $entity->id()) {
      $context['results']['success'][] = $sid;
    }
    else {
      $context['results']['errors'][] = [
        'word' => $word,
        'synonyms' => $synonyms
      ];
    }
  }

  /**
   * Batch finished callback.
   *
   * @param bool $success
   *   Was the batch successful or not?
   * @param array $result
   *   Array with the result of the import.
   * @param array $operations
   *   Batch operations.
   * @param string $elapsed
   *   Formatted string with the time batch operation was running.
   */
  public static function createSynonymBatchFinishedCallback($success, $result, $operations, $elapsed) {
    if ($success) {
      // Set message before returning to form.
      if (!empty($result['success'])) {
        $count = count($result['success']);
        $message = \Drupal::translation()->formatPlural($count,
          '@count synonym was successfully imported.',
          '@count synonyms was successfully imported.',
          ['@count' => $count]
        );
        drupal_set_message($message);
      }
    }
  }

  /**
   * Look up synonym.
   *
   * @param string $word
   *   The source word we add the synonym for.
   * @param string $type
   *   Synonym type.
   * @param string $langcode
   *   Language code.
   *
   * @return int
   *   Entity id for the found synonym.
   */
  public static function lookUpSynonym($word, $type, $langcode) {
    $query = \Drupal::database()->select('search_api_synonym', 's');
    $query->fields('s', ['sid']);
    $query->condition('s.type', $type);
    $query->condition('s.word', $word, 'LIKE');
    $query->condition('s.langcode', $langcode);
    $query->range(0, 1);
    return (int) $query->execute()->fetchField(0);
  }

}
