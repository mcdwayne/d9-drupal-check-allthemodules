<?php

namespace Drupal\field_collection_access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\field_collection\FieldCollectionItemInterface;
use Drupal\field_collection\Entity\FieldCollectionItem;

/**
 * Defines a storage handler for the field_collection_item grants system.
 *
 * This is used to build field collection query access.
 *
 * @ingroup field_collection_access
 */
class FieldCollectionItemAccessStorage {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a FieldCollectionItemAccessStorage object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(Connection $database, ModuleHandlerInterface $module_handler, LanguageManagerInterface $language_manager) {
    $this->database = $database;
    $this->moduleHandler = $module_handler;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function checkGrants(FieldCollectionItemInterface $fci, $operation, AccountInterface $account) {

    // Grants only support these operations.
    if (!in_array($operation, ['view', 'update', 'delete'])) {
      return AccessResult::neutral();
    }

    if ($this->hasBypassPermission($account)) {
      return AccessResult::allowed();
    }

    // If no module implements the hook don't query the database for grants.
    if (!$this->moduleHandler->getImplementations('field_collection_item_grants')) {
      // Return the equivalent of the default grant, defined by
      // self::writeDefault().
      if ($operation === 'view') {
        return AccessResult::allowed()->addCacheableDependency($fci);
      }
      else {
        return AccessResult::neutral();
      }
    }

    // Check the database for potential access grants.
    $query = $this->database->select('field_collection_access', 'base');
    $query->addExpression('1');
    // Only interested for granting in the current operation.
    $query->where('grant_' . $operation . '>= 1');
    $grants = $this->getUserGrants($operation, $account);
    $cond = new Condition('OR');
    if (count($grants)) {
      // Check for grants for this node and the correct langcode.
      $id = $query->andConditionGroup()
        ->condition('base.item_id', $fci->id())
        ->condition('langcode', $fci->language()->getId());
      $id->condition(static::buildFieldCollectionAccessCondition($grants));
      $cond->condition($id);
    }
    $cond->condition(static::defaultCondition($fci));

    $query->condition($cond);
    $query->range(0, 1);

    // Only the 'view' node grant can currently be cached; the others currently
    // don't have any cacheability metadata. Hopefully, we can add that in the
    // future, which would allow this access check result to be cacheable in all
    // cases. For now, this must remain marked as uncacheable, even when it is
    // theoretically cacheable, because we don't have the necessary metadata to
    // know it for a fact.
    $set_cacheability = function (AccessResult $access_result) use ($operation) {
      $access_result->addCacheContexts(['user.field_collection_access:' . $operation]);
      // If ($operation !== 'view') { // disable Caching of result.
      $access_result->setCacheMaxAge(0);
      // }.
      return $access_result;
    };

    if ($query->execute()->fetchField()) {
      return $set_cacheability(AccessResult::allowed());
    }
    else {
      return $set_cacheability(AccessResult::neutral());
    }
  }

  /**
   * Check if a given user has the permission to bypass fci access checks.
   */
  public function hasBypassPermission($user) {
    return $user->hasPermission('bypass field collection access');
  }

  /**
   * Empty records storage table in database.
   */
  public function deleteRecords() {
    $this->database->truncate('field_collection_access')->execute();
  }

  /**
   * Generate and insert default grant into database.
   */
  public function saveDefaultGrant() {
    $fields = [
      'item_id',
      'langcode',
      'fallback',
      'realm',
      'gid',
      'grant_view',
      'grant_update',
      'grant_delete',
    ];
    $query = $this->database->insert('field_collection_access')->fields($fields);
    $grant = [
      "item_id" => 0,
      "langcode" => 'en',
      "fallback" => 1,
      "realm" => 'all',
      "gid" => 0,
      "grant_view" => 1,
      "grant_update" => 1,
      "grant_delete" => 1,
    ];
    $query->values($grant);
    $query->execute();
  }

  /**
   * Saved records for a fci to the database.
   */
  public function saveRecords($fci, array $grants, $delete = TRUE) {
    if ($delete) {
      $query = $this->database->delete('field_collection_access')->condition('item_id', $fci->id());
      $query->execute();
    }
    // Only perform work when node_access modules are active.
    if (!empty($grants) && count($this->moduleHandler->getImplementations('field_collection_item_grants'))) {
      $fields = [
        'item_id',
        'langcode',
        'fallback',
        'realm',
        'gid',
        'grant_view',
        'grant_update',
        'grant_delete',
      ];
      $query = $this->database->insert('field_collection_access')->fields($fields);
      // If we have defined a granted langcode, use it. But if not, add a grant
      // for every language this fci is translated to.
      foreach ($grants as $grant) {
        if (isset($grant['langcode'])) {
          $grant_languages = [$grant['langcode'] => $this->languageManager->getLanguage($grant['langcode'])];
        }
        else {
          $grant_languages = $fci->getTranslationLanguages(TRUE);
        }
        foreach ($grant_languages as $grant_langcode => $grant_language) {
          // Only write grants; denies are implicit.
          if ($grant['grant_view'] || $grant['grant_update'] || $grant['grant_delete']) {
            $grant['item_id'] = $fci->id();
            $grant['langcode'] = $grant_langcode;
            // The record with the original langcode is used as the fallback.
            if ($grant['langcode'] == $fci->language()->getId()) {
              $grant['fallback'] = 1;
            }
            else {
              $grant['fallback'] = 0;
            }
            $query->values($grant);
          }
        }
      }
      $query->execute();
    }
  }

  /**
   * Reload all access field collection items.
   *
   * @param bool $batch_mode
   *   If true, create and execute rebuild as a batch job.
   */
  public function reloadRecords($batch_mode = FALSE) {
    if ($batch_mode) {
      $batch = [
        'title' => $this->t('Rebuilding Field Collection access permissions'),
        'operations' => [
          ['_field_collection_access_rebuild_batch_operation', []],
        ],
        'finished' => '_node_access_rebuild_batch_finished',
      ];
      batch_set($batch);
    }
    else {
      // Try to allocate enough time to rebuild node grants.
      drupal_set_time_limit(240);

      $fci_storage = \Drupal::entityManager()->getStorage('field_collection_item');
      $this->deleteRecords();
      $this->saveDefaultGrant();

      $entity_query = \Drupal::entityQuery('field_collection_item');
      $entity_query->sort('item_id', 'DESC');

      // Disable access checking since all entries must be processed even if the
      // user does not have access.
      $entity_query->accessCheck(FALSE);
      $ids = $entity_query->execute();

      foreach ($ids as $id) {
        $grants = [];
        $fci_storage->resetCache([$id]);
        $fci = FieldCollectionItem::load($id);
        // Only insert and save records for valid nodes.
        if (!empty($fci)) {
          $grants = $this->getRecordsFor($fci);
          $this->saveRecords($fci, $grants);
        }
      }
    }

    if (!isset($batch)) {
      drupal_set_message($this->t('Field collection permissions have been rebuilt.'));
      field_collection_access_needs_rebuild(FALSE);
    }
  }

  /**
   * Provide hook to generate record data for a given fci.
   */
  public function getRecordsFor(FieldCollectionItemInterface $fci) {
    $records = [];
    if ($this->moduleHandler->getImplementations('field_collection_item_grants')) {
      if ($this->moduleHandler->getImplementations('field_collection_item_access_records')) {
        $records = \Drupal::moduleHandler()->invokeAll('field_collection_item_access_records', [$fci]);
      }
    }
    return $records;
  }

  /**
   * Get access grants for a user (and op)
   */
  public function getUserGrants($op, $account) {
    $grants = [];
    if ($this->moduleHandler->getImplementations('field_collection_item_grants')) {
      $grants = \Drupal::moduleHandler()->invokeAll('field_collection_item_grants', [$op, $account]);
    }
    return $grants;
  }

  /**
   * Build default entry conditional for a fci.
   */
  protected function defaultCondition($fci) {
    $cond = new Condition("AND");

    $query = $this->database->select('field_collection_access', 'fcia');
    $query->addExpression('1');
    $query->condition('fcia.item_id', $fci->id());

    $cond->notExists($query);
    $cond->where('base.item_id = 0');

    return $cond;
  }

  /**
   * Build access condition for query.
   */
  protected function buildFieldCollectionAccessCondition(array $field_collection_grants) {
    $cond = new Condition("AND");

    $query = $this->database->select('field_collection_access', 'fcia');
    $query->addField('fcia', 'item_id');
    $query->where('fcia.item_id = base.item_id');
    $query->condition(static::buildGrantsCondition($field_collection_grants));

    $cond->exists($query);

    return $cond;
  }

  /**
   * Build grant condition set for grants.
   *
   * @param array $grants
   *   List of grants where 'realm' => array(...$gids)
   */
  protected function buildGrantsCondition(array $grants) {
    $cond = new Condition("OR");
    foreach ($grants as $realm => $gids) {
      if (!empty($gids)) {
        $and = new Condition('AND');
        $cond->condition($and
          ->condition('gid', $gids, 'IN')
          ->condition('realm', $realm)
        );
      }
    }
    return $cond;
  }

  /**
   * Count the number of rows in the table.
   */
  public function count() {
    return $this->database->query('SELECT COUNT(*) FROM {field_collection_access}')->fetchField();
  }

}
