<?php

namespace Drupal\smallads\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface for smallad entity.
 */
interface SmalladInterface extends ContentEntityInterface {


  /**
   *  Like an unpublished node.
   */
  const SCOPE_PRIVATE = 0;
  /**
   * Visible to members of the same groups as the Owner.
   */
  const SCOPE_GROUP = 1;
  /**
   * Visible to anyone on the same (Drupal) site.
   */
  const SCOPE_SITE = 2;
  /**
   * Visble to members of other sites via an API.
   */
  const SCOPE_NETWORK = 3;
  /**
   * Visible to strangers.
   */
  const SCOPE_PUBLIC = 4;

  /**
   * Get the default expiry time for a new small ad.
   *
   * @return int
   *   the unixtime
   */
  public static function expiresDefault();

  /**
   * Cause the smallad to expire i.e. reduce the visibility to 0.
   */
  public function expire();

  /**
   * Get the time the node was last changed.
   *
   * @return int
   *   The unixtime.
   */
  public function getChangedTime();


  /**
   * Returns the time that the smallad was created.
   *
   * @return int
   *   The timestamp of when the smallad was created.
   */
  public function getCreatedTime();
}
