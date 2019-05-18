<?php

namespace Drupal\cloud\Entity;

use Drupal\cloud\CloudContextInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Cloud Server Template entities.
 *
 * @ingroup cloud_server_template
 */
interface CloudServerTemplateInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface, CloudContextInterface {

  /**
   * Gets the Cloud Server Template name.
   *
   * @return string
   *   Name of the Cloud Server Template.
   */
  public function getName();

  /**
   * Sets the Cloud Server Template name.
   *
   * @param string $name
   *   The Cloud Server Template name.
   *
   * @return \Drupal\cloud\Entity\CloudServerTemplateInterface
   *   The called Cloud Server Template entity.
   */
  public function setName($name);

  /**
   * Gets the Cloud Server Template creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Cloud Server Template.
   */
  public function getCreatedTime();

  /**
   * Sets the Cloud Server Template creation timestamp.
   *
   * @param int $timestamp
   *   The Cloud Server Template creation timestamp.
   *
   * @return \Drupal\cloud\Entity\CloudServerTemplateInterface
   *   The called Cloud Server Template entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Cloud Server Template published status indicator.
   *
   * Unpublished Cloud Server Template are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Cloud Server Template is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Cloud Server Template.
   *
   * @param bool $published
   *   TRUE to set this Cloud Server Template to published, FALSE to set it to
   *   unpublished.
   *
   * @return \Drupal\cloud\Entity\CloudServerTemplateInterface
   *   The called Cloud Server Template entity.
   */
  public function setPublished($published);

  /**
   * Gets the Cloud Server Template revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Cloud Server Template revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\cloud\Entity\CloudServerTemplateInterface
   *   The called Cloud Server Template entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Cloud Server Template revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Cloud Server Template revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\cloud\Entity\CloudServerTemplateInterface
   *   The called Cloud Server Template entity.
   */
  public function setRevisionUserId($uid);

}
