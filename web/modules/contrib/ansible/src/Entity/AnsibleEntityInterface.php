<?php

namespace Drupal\ansible\Entity;

use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface for defining Ansible entity entities.
 *
 * @ingroup ansible
 */
interface AnsibleEntityInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Drupal Domain name.
   *
   * @return string
   *   Name of the Drupal Domain.
   */
  public function getName();

  /**
   * Sets the Drupal Domain name.
   *
   * @param string $name
   *   The Drupal Domain name.
   *
   * @return \Drupal\ansible_udl_form\Entity\AnsibleUdLDomainInterface
   *   The called Drupal Domain entity.
   */
  public function setName($name);

  /**
   * Gets the Drupal Domain creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Drupal Domain.
   */
  public function getCreatedTime();

  /**
   * Sets the Drupal Domain creation timestamp.
   *
   * @param int $timestamp
   *   The Drupal Domain creation timestamp.
   *
   * @return \Drupal\ansible_udl_form\Entity\AnsibleUdLDomainInterface
   *   The called Drupal Domain entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Drupal Domain published status indicator.
   *
   * Unpublished Drupal Domain are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Drupal Domain is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Drupal Domain.
   *
   * @param bool $published
   *   TRUE to set this Drupal Domain to published,
   *   FALSE to set it to unpublished.
   *
   * @return \Drupal\ansible_udl_form\Entity\AnsibleUdLDomainInterface
   *   The called Drupal Domain entity.
   */
  public function setPublished($published);

}
