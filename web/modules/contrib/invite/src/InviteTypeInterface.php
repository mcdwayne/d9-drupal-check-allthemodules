<?php

namespace Drupal\invite;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Invite type entities.
 */
interface InviteTypeInterface extends ConfigEntityInterface {

  /**
   * Gets the Invite type label.
   *
   * @return string
   *   Label of the Invite type.
   */
  public function label();

  /**
   * Sets the Invite type label.
   *
   * @param string $label
   *   The Invite type label.
   *
   * @return \Drupal\invite\InviteTypeInterface
   *   The called Invite type entity.
   */
  public function setLabel($label);

  /**
   * Gets the Invite type type.
   *
   * @return string
   *   Type of the Invite type.
   */
  public function getType();

  /**
   * Sets the Invite type type.
   *
   * @param string $type
   *   The Invite type type.
   *
   * @return \Drupal\invite\InviteTypeInterface
   *   The called Invite type entity.
   */
  public function setType($type);

}
