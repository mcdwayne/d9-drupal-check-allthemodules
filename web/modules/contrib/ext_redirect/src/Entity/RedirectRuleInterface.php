<?php

namespace Drupal\ext_redirect\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Redirect Rule entities.
 *
 * @ingroup ext_redirect
 */
interface RedirectRuleInterface extends  ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Redirect Rule name.
   *
   * @return string
   *   Name of the Redirect Rule.
   */
  public function getName();

  /**
   * Sets the Redirect Rule name.
   *
   * @param string $name
   *   The Redirect Rule name.
   *
   * @return \Drupal\ext_redirect\Entity\RedirectRuleInterface
   *   The called Redirect Rule entity.
   */
  public function setName($name);

  /**
   * Gets the Redirect Rule creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Redirect Rule.
   */
  public function getCreatedTime();

  /**
   * Sets the Redirect Rule creation timestamp.
   *
   * @param int $timestamp
   *   The Redirect Rule creation timestamp.
   *
   * @return \Drupal\ext_redirect\Entity\RedirectRuleInterface
   *   The called Redirect Rule entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Redirect Rule published status indicator.
   *
   * Unpublished Redirect Rule are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Redirect Rule is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Redirect Rule.
   *
   * @param bool $published
   *   TRUE to set this Redirect Rule to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\ext_redirect\Entity\RedirectRuleInterface
   *   The called Redirect Rule entity.
   */
  public function setPublished($published);

}
