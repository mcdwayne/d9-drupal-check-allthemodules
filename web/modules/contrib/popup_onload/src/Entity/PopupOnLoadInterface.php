<?php

namespace Drupal\popup_onload\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Popup On Load entities.
 *
 * @ingroup popup_onload
 */
interface PopupOnLoadInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Popup On Load name.
   *
   * @return string
   *   Name of the Popup On Load.
   */
  public function getName();

  /**
   * Sets the Popup On Load name.
   *
   * @param string $name
   *   The Popup On Load name.
   *
   * @return \Drupal\popup_onload\Entity\PopupOnLoadInterface
   *   The called Popup On Load entity.
   */
  public function setName($name);

  /**
   * Gets the Popup On Load creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Popup On Load.
   */
  public function getCreatedTime();

  /**
   * Sets the Popup On Load creation timestamp.
   *
   * @param int $timestamp
   *   The Popup On Load creation timestamp.
   *
   * @return \Drupal\popup_onload\Entity\PopupOnLoadInterface
   *   The called Popup On Load entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Popup On Load published status indicator.
   *
   * Unpublished Popup On Load are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Popup On Load is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Popup On Load.
   *
   * @param bool $published
   *   TRUE to set this Popup On Load to published,
   *   FALSE to set it to unpublished.
   *
   * @return \Drupal\popup_onload\Entity\PopupOnLoadInterface
   *   The called Popup On Load entity.
   */
  public function setPublished($published);

}
