<?php

namespace Drupal\merci_line_item\Entity;

use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Merci Line Item entities.
 *
 * @ingroup merci_line_item
 */
interface MerciLineItemInterface extends RevisionableInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Merci Line Item type.
   *
   * @return string
   *   The Merci Line Item type.
   */
  public function getType();

  /**
   * Gets the Merci Line Item name.
   *
   * @return string
   *   Name of the Merci Line Item.
   */
  public function getName();

  /**
   * Sets the Merci Line Item name.
   *
   * @param string $name
   *   The Merci Line Item name.
   *
   * @return \Drupal\merci_line_item\Entity\MerciLineItemInterface
   *   The called Merci Line Item entity.
   */
  public function setName($name);

  /**
   * Gets the Merci Line Item creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Merci Line Item.
   */
  public function getCreatedTime();

  /**
   * Sets the Merci Line Item creation timestamp.
   *
   * @param int $timestamp
   *   The Merci Line Item creation timestamp.
   *
   * @return \Drupal\merci_line_item\Entity\MerciLineItemInterface
   *   The called Merci Line Item entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Merci Line Item published status indicator.
   *
   * Unpublished Merci Line Item are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Merci Line Item is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Merci Line Item.
   *
   * @param bool $published
   *   TRUE to set this Merci Line Item to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\merci_line_item\Entity\MerciLineItemInterface
   *   The called Merci Line Item entity.
   */
  public function setPublished($published);

  /**
   * Gets the Merci Line Item revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Merci Line Item revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\merci_line_item\Entity\MerciLineItemInterface
   *   The called Merci Line Item entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Merci Line Item revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Merci Line Item revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\merci_line_item\Entity\MerciLineItemInterface
   *   The called Merci Line Item entity.
   */
  public function setRevisionUserId($uid);

}
