<?php

namespace Drupal\search_api_saved_searches;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\search_api\Query\QueryInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for saved search entities.
 */
interface SavedSearchInterface extends ContentEntityInterface, EntityOwnerInterface {

  /**
   * Retrieves the type (bundle) entity for this saved search.
   *
   * @return \Drupal\search_api_saved_searches\SavedSearchTypeInterface
   *   The type entity for this saved search.
   *
   * @throws \Drupal\search_api_saved_searches\SavedSearchesException
   *   Thrown if the type is unknown.
   */
  public function getType();

  /**
   * Retrieves the search query of this saved search.
   *
   * @return \Drupal\search_api\Query\QueryInterface|null
   *   The search query of this saved search, or NULL if it couldn't be
   *   retrieved.
   */
  public function getQuery();

  /**
   * Sets the search query.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The new query.
   *
   * @return $this
   */
  public function setQuery(QueryInterface $query);

  /**
   * Retrieves the path to the saved search's original search page.
   *
   * @return string|null
   *   An internal path to the original search page for this saved search, or
   *   NULL if there was none set.
   */
  public function getPath();

  /**
   * Generates an access token specific to this saved search.
   *
   * This can be used for access checks independent of a user account (for
   * instance, for accessing a saved search via mail – especially for anonymous
   * users).
   *
   * @param string $operation
   *   The operation to perform on the saved search entity. The returned token
   *   will be only valid for this operation.
   *
   * @return string
   *   The access token for executing the given operation on this search.
   */
  public function getAccessToken($operation);

}
