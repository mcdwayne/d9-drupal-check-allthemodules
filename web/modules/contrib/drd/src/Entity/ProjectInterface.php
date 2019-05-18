<?php

namespace Drupal\drd\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Project entities.
 *
 * @ingroup drd
 */
interface ProjectInterface extends UpdateStatusInterface, ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Get language code of the project.
   *
   * @return string
   *   Language code.
   */
  public function getLangCode();

  /**
   * Gets the Project label.
   *
   * @return string
   *   Label of the Project.
   */
  public function getLabel();

  /**
   * Sets the Project label.
   *
   * @param string $label
   *   The Project label.
   *
   * @return $this
   */
  public function setLabel($label);

  /**
   * Gets the Project name.
   *
   * @return string
   *   Name of the Project.
   */
  public function getName();

  /**
   * Sets the Project name.
   *
   * @param string $name
   *   The Project name.
   *
   * @return $this
   */
  public function setName($name);

  /**
   * Gets the Project type.
   *
   * @return string
   *   Name of the Project.
   */
  public function getType();

  /**
   * Sets the Project type.
   *
   * @param string $type
   *   The Project type.
   *
   * @return $this
   */
  public function setType($type);

  /**
   * Gets the Project creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Project.
   */
  public function getCreatedTime();

  /**
   * Sets the Project creation timestamp.
   *
   * @param int $timestamp
   *   The Project creation timestamp.
   *
   * @return $this
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Project published status indicator.
   *
   * Unpublished Project are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Project is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Project.
   *
   * @param bool $published
   *   TRUE to set this Project to published, FALSE to set it to unpublished.
   *
   * @return $this
   */
  public function setPublished($published);

  /**
   * Get project's URL on drupal.org.
   *
   * @return \Drupal\Core\Url
   *   The project url.
   */
  public function getProjectLink();

  /**
   * Create new or return existing project entity.
   *
   * @param string $type
   *   The project type.
   * @param string $name
   *   The project name.
   *
   * @return \Drupal\drd\Entity\ProjectInterface
   *   The project entity.
   */
  public static function findOrCreate($type, $name);

  /**
   * Find existing project entity.
   *
   * @param string $name
   *   The project name.
   *
   * @return \Drupal\drd\Entity\ProjectInterface|bool
   *   The project entity or False if not found.
   */
  public static function find($name);

}
