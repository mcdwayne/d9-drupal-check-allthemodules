<?php

namespace Drupal\bibcite_entity\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Reference entities.
 *
 * @ingroup bibcite_entity
 */
interface ReferenceInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Reference creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Reference.
   */
  public function getCreatedTime();

  /**
   * Sets the Reference creation timestamp.
   *
   * @param int $timestamp
   *   The Reference creation timestamp.
   *
   * @return \Drupal\bibcite_entity\Entity\ReferenceInterface
   *   The called Reference entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Render reference entity as citation.
   *
   * @param string|null $style
   *   Identifier of citation style.
   *   Default style will be used if this value is NULL.
   *
   * @return mixed
   *   Rendered citation.
   */
  public function cite($style = NULL);

}
