<?php

namespace Drupal\drd\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Requirement entities.
 *
 * @ingroup drd
 */
interface RequirementInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Get language code of the requirement.
   *
   * @return string
   *   Language code.
   */
  public function getLangCode();

  /**
   * Gets the Requirement name.
   *
   * @return string
   *   Name of the Requirement.
   */
  public function getName();

  /**
   * Sets the Requirement name.
   *
   * @param string $name
   *   The Requirement name.
   *
   * @return $this
   */
  public function setName($name);

  /**
   * Gets the Requirement creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Requirement.
   */
  public function getCreatedTime();

  /**
   * Sets the Requirement creation timestamp.
   *
   * @param int $timestamp
   *   The Requirement creation timestamp.
   *
   * @return $this
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Requirement published status indicator.
   *
   * Unpublished Requirement are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Requirement is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Requirement.
   *
   * @param bool $published
   *   TRUE to set this Requirement to published, FALSE otherwise.
   *
   * @return $this
   */
  public function setPublished($published);

  /**
   * Returns the Requirements ignored indicator.
   *
   * @return bool
   *   TRUE if the Requirement is ignored.
   */
  public function isIgnored();

  /**
   * Create new or return existing requirement entity.
   *
   * @param string $key
   *   The key of the requirement.
   * @param string $label
   *   The label for this requirement.
   *
   * @return \Drupal\drd\Entity\RequirementInterface
   *   The requirement entity.
   *
   * @throws \Exception
   */
  public static function findOrCreate($key, $label);

  /**
   * Gets the category of this requirement.
   *
   * @return string
   *   Category of the Requirement.
   */
  public function getCategory();

  /**
   * Get a list of category keys.
   *
   * @return array
   *   The list of keys.
   */
  public static function getCategoryKeys();

}
