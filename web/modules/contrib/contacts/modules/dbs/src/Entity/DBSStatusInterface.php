<?php

namespace Drupal\contacts_dbs\Entity;

use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining an entity for dbs status items.
 */
interface DBSStatusInterface extends ContentEntityInterface, EntityChangedInterface, RevisionLogInterface, EntityOwnerInterface {

  /**
   * Defines the format that dates should be stored in.
   */
  const DATE_FORMAT = DateTimeItemInterface::DATE_STORAGE_FORMAT;

  /**
   * Archive this dbs status entity.
   *
   * @return $this
   */
  public function archive();

  /**
   * Returns the dbs status item creation timestamp.
   *
   * @todo Remove and use the new interface when #2833378 is done.
   * @see https://www.drupal.org/node/2833378
   *
   * @return int
   *   Creation timestamp of the dbs status item.
   */
  public function getCreatedTime();

  /**
   * Sets the dbs status item creation timestamp.
   *
   * @todo Remove and use the new interface when #2833378 is done.
   * @see https://www.drupal.org/node/2833378
   *
   * @param int $timestamp
   *   The dbs status creation timestamp.
   *
   * @return \Drupal\contacts_dbs\Entity\DBSStatusInterface
   *   The called dbs status item.
   */
  public function setCreatedTime($timestamp);

  /**
   * Check that status is valid at certain time.
   *
   * @param int|null $valid_at
   *   The timestamp to check against or current if none given.
   *
   * @return bool
   *   Whether or not the currect status is valid.
   */
  public function isValid($valid_at = NULL);

  /**
   * Get a list of status that count as cleared.
   *
   * @return array
   *   Array of statuses.
   */
  public static function getClearedStatuses();

}
