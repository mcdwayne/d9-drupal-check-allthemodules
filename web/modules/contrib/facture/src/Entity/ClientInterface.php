<?php

namespace Drupal\facture\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Client entities.
 *
 * @ingroup facture
 */
interface ClientInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Client name.
   *
   * @return string
   *   Name of the Client.
   */
  public function getName();

  /**
   * Sets the Client name.
   *
   * @param string $name
   *   The Client name.
   *
   * @return \Drupal\facture\Entity\ClientInterface
   *   The called Client entity.
   */
  public function setName($name);

  /**
   * Gets the Client creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Client.
   */
  public function getCreatedTime();

  /**
   * Sets the Client creation timestamp.
   *
   * @param int $timestamp
   *   The Client creation timestamp.
   *
   * @return \Drupal\facture\Entity\ClientInterface
   *   The called Client entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Client published status indicator.
   *
   * Unpublished Client are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Client is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Client.
   *
   * @param bool $published
   *   TRUE to set this Client to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\facture\Entity\ClientInterface
   *   The called Client entity.
   */
  public function setPublished($published);

  /**
   * Gets the Client revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Client revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\facture\Entity\ClientInterface
   *   The called Client entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Client revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Client revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\facture\Entity\ClientInterface
   *   The called Client entity.
   */
  public function setRevisionUserId($uid);

}
