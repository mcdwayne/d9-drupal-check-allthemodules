<?php

namespace Drupal\invite;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Invite sender entities.
 */
interface InviteSenderInterface extends ConfigEntityInterface {

  /**
   * Gets the Invite Sender label.
   *
   * @return string
   *   Label of the Invite Sender.
   */
  public function label();

  /**
   * Sets the Invite Sender label.
   *
   * @param string $label
   *   The Invite Sender label.
   *
   * @return \Drupal\invite\InviteTypeInterface
   *   The called Invite Sender entity.
   */
  public function setLabel($label);

  /**
   * Gets the Invite Sender type.
   *
   * @return string
   *   Type of the Invite Sender.
   */
  public function getType();

  /**
   * Sets the Invite Sender type.
   *
   * @param string $type
   *   The Invite Sender type.
   *
   * @return \Drupal\invite\InviteTypeInterface
   *   The called Invite Sender entity.
   */
  public function setType($type);

}
