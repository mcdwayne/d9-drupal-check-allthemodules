<?php

/**
 * @file
 * Contains \Drupal\collect\CollectStorageInterface.
 */

namespace Drupal\collect;

use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines an interface for collect container entity storage classes.
 */
interface CollectStorageInterface extends EntityStorageInterface {

  /**
   * Loads the container with the given Origin URI.
   *
   * @param string $origin_uri
   *   The Origin URI to look for.
   *
   * @return \Drupal\collect\CollectContainerInterface|null
   *   The container with the given Origin URI. If there are multiple containers
   *   with the same URI, the latest is returned.
   */
  public function loadByOriginUri($origin_uri);

  /**
   * Returns a list of collect container revision IDs for a specific container.
   *
   * @param \Drupal\collect\CollectContainerInterface $collect_container
   *   The collect container entity.
   *
   * @return int[]
   *   Collect container revision IDs (in ascending order).
   */
  public function revisionIds(CollectContainerInterface $collect_container);

  /**
   * Returns ids of containers whose schema URI match the given pattern.
   *
   * @param string[] $uri_patterns
   *   The URI patterns to match.
   * @param int|null $limit
   *   The maximum number of items to return.
   * @param int|null $offset
   *   Number of items to skip.
   *
   * @return int[]
   *   A list of entity ids, keyed by revision ids.
   */
  public function getIdsByUriPatterns(array $uri_patterns, $limit = NULL, $offset = NULL);

  /**
   * Saves a new container item or updates an existing one.
   *
   * @param \Drupal\collect\CollectContainerInterface $container
   *   The container to save.
   * @param bool $is_container_revision
   *   Whether the model of this container is revisionable. If TRUE, and there
   *   exists a container with the same Origin URI as the one to be persisted,
   *   then the given container is saved as a revision of the existing one. (If
   *   the data is unchanged, the container is not saved at all.) If FALSE, the
   *   container will simply be saved as a new entity.
   *
   * @return \Drupal\collect\CollectContainerInterface
   *   The created or updated container.
   */
  public function persist(CollectContainerInterface $container, $is_container_revision = FALSE);

}
