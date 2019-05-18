<?php

namespace Drupal\search_api_saved_searches\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\search_api\LoggerTrait;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\SearchApiException;
use Drupal\search_api\Utility\Utility;
use Drupal\search_api_saved_searches\SavedSearchesException;
use Drupal\search_api_saved_searches\SavedSearchInterface;
use Psr\Log\LoggerInterface;

/**
 * Provides a service for checking saved searches for new results.
 */
class NewResultsCheck {

  use LoggerTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new class instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger to use.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, ConfigFactoryInterface $configFactory, TimeInterface $time, LoggerInterface $logger) {
    $this->entityTypeManager = $entityTypeManager;
    $this->configFactory = $configFactory;
    $this->time = $time;
    $this->logger = $logger;
  }

  /**
   * Retrieves the saved search entity storage.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The saved search entity storage.
   */
  protected function getSearchStorage() {
    return $this->entityTypeManager->getStorage('search_api_saved_search');
  }

  /**
   * Retrieves the saved search type entity storage.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The saved search type entity storage.
   */
  protected function getSearchTypeStorage() {
    return $this->entityTypeManager->getStorage('search_api_saved_search_type');
  }

  /**
   * Checks all saved searches that are "due" for new results.
   *
   * @param string|null $type_id
   *   (optional) The type of saved searches to check, or NULL to check searches
   *   for all enabled types that have at least one notification plugin set.
   *
   * @return int
   *   The number of saved searches that were successfully checked for new
   *   results.
   */
  public function checkAll($type_id = NULL) {
    $search_ids = $this->getSearchesToCheck($type_id);
    if (!$search_ids) {
      return 0;
    }

    $count = 0;
    $now = $this->time->getRequestTime();

    /** @var \Drupal\search_api_saved_searches\SavedSearchInterface $search */
    foreach ($this->getSearchStorage()->loadMultiple($search_ids) as $search) {
      try {
        $results = $this->getNewResults($search);
        $search->set('last_executed', $now);
        $search->save();
        ++$count;
        if (!$results) {
          continue;
        }
        foreach ($search->getType()->getNotificationPlugins() as $plugin) {
          $plugin->notify($search, $results);
        }
      }
      // @todo Use multi-catch for SavedSearchesException and
      //   EntityStorageException once we're allowed to use PHP 7.1+.
      catch (\Exception $e) {
        $args['@search_id'] = $search->id();
        watchdog_exception('search_api_saved_searches', $e, '%type while trying to find new results for saved search #@search_id: @message in %function (line %line of %file).', $args);
      }
    }

    return $count;
  }

  /**
   * Determines the saved searches that should be checked for new results.
   *
   * @param string|null $type_id
   *   (optional) The type of saved searches to check, or NULL to check searches
   *   for all enabled types that have at least one notification plugin set.
   *
   * @return int[]
   *   The entity IDs of all saved searches that should be checked.
   */
  public function getSearchesToCheck($type_id = NULL) {
    $now = $this->time->getRequestTime();

    $query = $this->getSearchStorage()->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', TRUE)
      // Add a small amount to the current time, so small differences in
      // execution time don't result in a delay until the next cron run.
      ->condition('next_execution', $now + 15, '<=');
    if ($type_id !== NULL) {
      $query->condition('type', $type_id);
    }
    else {
      $types = $this->getTypesWithNotification();
      if ($types !== NULL) {
        if (!$types) {
          return [];
        }
        $query->condition('type', $types, 'IN');
      }
    }

    // Limit the number of searches to check in a single request, unless we're
    // running in the CLI (where we don't have to worry about the maximum
    // execution time).
    if (!Utility::isRunningInCli()) {
      $limit = $this->configFactory
        ->get('search_api_saved_searches.settings')
        ->get('cron_batch_size');
      if ($limit > 0) {
        $query->sort('next_execution');
        $query->range(0, $limit);
      }
    }

    // Add a tag to make it easy for other modules to alter this query.
    $query->addTag('search_api_saved_searches_to_check');

    return $query->execute();
  }

  /**
   * Retrieves the saved search types that have any notification plugins set.
   *
   * @return string[]|null
   *   Either an array containing the IDs of all saved search types that are
   *   both enabled and have at least one notification plugin set (which might
   *   be an empty array). Or NULL if all existing types match these criteria.
   */
  public function getTypesWithNotification() {
    /** @var \Drupal\search_api_saved_searches\SavedSearchTypeInterface[] $types */
    $types = $this->getSearchTypeStorage()->loadMultiple();
    $all = TRUE;

    foreach ($types as $id => $type) {
      if (!$type->status() || !$type->getNotificationPluginIds()) {
        unset($types[$id]);
        $all = FALSE;
      }
    }

    return $all ? NULL : array_keys($types);
  }

  /**
   * Retrieves new results for the given search.
   *
   * @param \Drupal\search_api_saved_searches\SavedSearchInterface $search
   *   The saved search to check for new results.
   *
   * @return \Drupal\search_api\Query\ResultSetInterface|null
   *   The new results, if any could be found. NULL otherwise.
   *
   * @throws \Drupal\search_api_saved_searches\SavedSearchesException
   *   Thrown if an error was encountered (like an invalid type or query, or the
   *   search query failing).
   */
  public function getNewResults(SavedSearchInterface $search) {
    $search_id = $search->id();
    $type = $search->getType();
    $query = $search->getQuery();
    if (!$query) {
      throw new SavedSearchesException("Saved search #$search_id does not have a valid query set");
    }
    // Clone the query to make sure we don't make any modifications to its
    // stored version.
    $query = clone $query;
    $index_id = $query->getIndex()->id();
    $date_field = $type->getOption("date_field.$index_id");
    if ($date_field) {
      $query->addCondition($date_field, $search->get('last_executed')->value, '>');
    }

    // Unify some general query options.
    $query->setProcessingLevel(QueryInterface::PROCESSING_BASIC);
    $query->setSearchId("search_api_saved_searches:$search_id");
    $query->range(NULL, NULL);

    try {
      // Pass the query to the server directly (since the query is already
      // marked as "executed", so calling $query->execute() wouldn't do
      // anything).
      $query->getIndex()->getServerInstance()->search($query);
      $query->postExecute();
      $results = $query->getResults();
    }
    catch (SearchApiException $e) {
      $class = get_class($e);
      throw new SavedSearchesException("$class while executing the search query for saved search #$search_id: {$e->getMessage()}", 0, $e);
    }

    // If there were no results at all, we're done.
    if (!$results->getResultCount()) {
      return NULL;
    }
    // Same when we used a date field, but in this case with results.
    if ($date_field) {
      return $results;
    }

    // Otherwise, we need to match the current results' IDs to the known ones.
    $old_result_ids = Database::getConnection()
      ->select('search_api_saved_searches_old_results', 't')
      ->fields('t', ['item_id'])
      ->condition('search_id', $search_id)
      ->execute()
      ->fetchCol();
    $items = $results->getResultItems();
    $items = array_diff_key($items, array_flip($old_result_ids));

    if ($items) {
      $results->setResultCount(count($items));
      $results->setResultItems($items);
      if (!$this->saveKnownResults($search, $items)) {
        // To avoid reporting the same results again, better report no results
        // right now and hope the error gets resolved.
        return NULL;
      }
    }

    return $items ? $results : NULL;
  }

  /**
   * Saves the known ("old") results for a saved search.
   *
   * @param \Drupal\search_api_saved_searches\SavedSearchInterface $search
   *   The saved search.
   * @param \Drupal\search_api\Item\ItemInterface[] $items
   *   The known results to save. Passing already saved results here will cause
   *   this method to fail.
   *
   * @return bool
   *   TRUE if the operation succeeded, FALSE otherwise.
   */
  public function saveKnownResults(SavedSearchInterface $search, array $items) {
    $insert = Database::getConnection()
      ->insert('search_api_saved_searches_old_results')
      ->fields(['search_id', 'search_type', 'item_id']);
    $search_id = $search->id();
    $type_id = $search->bundle();
    foreach (array_keys($items) as $id) {
      $insert->values([
        'search_id' => $search_id,
        'search_type' => $type_id,
        'item_id' => $id,
      ]);
    }
    try {
      $insert->execute();
      return TRUE;
    }
    catch (\Exception $e) {
      $vars['@search_id'] = $search->id();
      $vars['%search_label'] = $search->label();
      $this->logException($e, '%type while trying to save known results for saved search #@search_id (%search_label): @message in %function (line %line of %file).', $vars);
      return FALSE;
    }
  }

}
