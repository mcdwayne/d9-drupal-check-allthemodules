<?php

namespace Drupal\bibcite_entity\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface for defining Contributor entities.
 *
 * @ingroup bibcite_entity
 */
interface ContributorInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the Contributor name.
   *
   * @return string
   *   Name of the Contributor.
   */
  public function getName();

  /**
   * Get the Contributor first name.
   *
   * @return string
   *   First name of Contributor.
   */
  public function getFirstName();

  /**
   * Get the Contributor middle name.
   *
   * @return string
   *   Middle name of Contributor.
   */
  public function getMiddleName();

  /**
   * Get the Contributor last name.
   *
   * @return string
   *   Last name of Contributor.
   */
  public function getLastName();

  /**
   * Get the Contributor nickname.
   *
   * @return string
   *   Nickname of Contributor.
   */
  public function getNickName();

  /**
   * Get the Contributor suffix.
   *
   * @return string
   *   Suffix of Contributor.
   */
  public function getSuffix();

  /**
   * Get the Contributor prefix.
   *
   * @return string
   *   Prefix of Contributor.
   */
  public function getPrefix();

  /**
   * Get the Contributor leading initial.
   *
   * @return string
   *   Leading initial of Contributor.
   */
  public function getLeadingInitial();

  /**
   * Sets the Contributor full name.
   *
   * Full name string will be parsed and another fields will be changed.
   *
   * @param string $name
   *   Full name sting.
   *
   * @return \Drupal\bibcite_entity\Entity\ContributorInterface
   *   The called Contributor entity.
   */
  public function setName($name);

  /**
   * Sets the Contributor first name.
   *
   * @param string $first_name
   *   The Contributor first name.
   *
   * @return \Drupal\bibcite_entity\Entity\ContributorInterface
   *   The called Contributor entity.
   */
  public function setFirstName($first_name);

  /**
   * Sets the Contributor middle name.
   *
   * @param string $middle_name
   *   The Contributor middle name.
   *
   * @return \Drupal\bibcite_entity\Entity\ContributorInterface
   *   The called Contributor entity.
   */
  public function setMiddleName($middle_name);

  /**
   * Sets the Contributor last name.
   *
   * @param string $last_name
   *   The Contributor last name.
   *
   * @return \Drupal\bibcite_entity\Entity\ContributorInterface
   *   The called Contributor entity.
   */
  public function setLastName($last_name);

  /**
   * Sets the Contributor nickname.
   *
   * @param string $nick
   *   The Contributor nickname.
   *
   * @return \Drupal\bibcite_entity\Entity\ContributorInterface
   *   The called Contributor entity.
   */
  public function setNickName($nick);

  /**
   * Sets the Contributor suffix.
   *
   * @param string $suffix
   *   The Contributor suffix.
   *
   * @return \Drupal\bibcite_entity\Entity\ContributorInterface
   *   The called Contributor entity.
   */
  public function setSuffix($suffix);

  /**
   * Sets the Contributor prefix.
   *
   * @param string $prefix
   *   The Contributor prefix.
   *
   * @return \Drupal\bibcite_entity\Entity\ContributorInterface
   *   The called Contributor entity.
   */
  public function setPrefix($prefix);

  /**
   * Sets the Contributor leading initial.
   *
   * @param string $leading
   *   The Contributor leading initial.
   *
   * @return \Drupal\bibcite_entity\Entity\ContributorInterface
   *   The called Contributor entity.
   */
  public function setLeadingInitial($leading);

  /**
   * Gets the Contributor creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Contributor.
   */
  public function getCreatedTime();

  /**
   * Sets the Contributor creation timestamp.
   *
   * @param int $timestamp
   *   The Contributor creation timestamp.
   *
   * @return \Drupal\bibcite_entity\Entity\ContributorInterface
   *   The called Contributor entity.
   */
  public function setCreatedTime($timestamp);

}
