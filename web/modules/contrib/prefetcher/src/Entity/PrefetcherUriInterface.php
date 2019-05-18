<?php

namespace Drupal\prefetcher\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Prefetcher uri entities.
 *
 * @ingroup prefetcher
 */
interface PrefetcherUriInterface extends  ContentEntityInterface, EntityChangedInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Prefetcher uri name.
   *
   * @return string
   *   Name of the Prefetcher uri.
   */
  #public function getName();

  /**
   * Sets the Prefetcher uri name.
   *
   * @param string $name
   *   The Prefetcher uri name.
   *
   * @return \Drupal\prefetcher\Entity\PrefetcherUriInterface
   *   The called Prefetcher uri entity.
   */
  #public function setName($name);

  /**
   * Gets the Prefetcher uri creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Prefetcher uri.
   */
  public function getCreatedTime();

  /**
   * Sets the Prefetcher uri creation timestamp.
   *
   * @param int $timestamp
   *   The Prefetcher uri creation timestamp.
   *
   * @return \Drupal\prefetcher\Entity\PrefetcherUriInterface
   *   The called Prefetcher uri entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Prefetcher uri published status indicator.
   *
   * Unpublished Prefetcher uri are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Prefetcher uri is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Prefetcher uri.
   *
   * @param bool $published
   *   TRUE to set this Prefetcher uri to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\prefetcher\Entity\PrefetcherUriInterface
   *   The called Prefetcher uri entity.
   */
  public function setPublished($published);

  /**
   * Enqueues the uri to be crawled as soon as possible.
   */
  public function jumpTheQueue();

  public function getUri();
  public function setUri($uri);
  public function getPath();
  public function setPath($path);

}
