<?php
/**
 * @file
 * Contains \Drupal\collect\Relation\RelationInterface.
 */

namespace Drupal\collect\Relation;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface for the relation entity.
 */
interface RelationInterface extends ContentEntityInterface {

  /**
   * Returns the source URI.
   *
   * @returns string
   *   The source URI.
   */
  public function getSourceUri();

  /**
   * Sets the source URI.
   *
   * @param string $source_uri
   *   The new source URI.
   *
   * @returns $this
   */
  public function setSourceUri($source_uri);

  /**
   * Returns the source ID.
   *
   * @returns int
   *   The source ID.
   */
  public function getSourceId();

  /**
   * Sets the source ID.
   *
   * @param int $source_id
   *   The new source ID.
   *
   * @returns $this
   */
  public function setSourceId($source_id);

  /**
   * Returns the target URI.
   *
   * @returns string
   *   The target URI.
   */
  public function getTargetUri();

  /**
   * Sets the target URI.
   *
   * @param string $target_uri
   *   The new target URI.
   *
   * @returns $this
   */
  public function setTargetUri($target_uri);

  /**
   * Returns the target ID.
   *
   * @returns string
   *   The target ID.
   */
  public function getTargetId();

  /**
   * Sets the target ID.
   *
   * @param string $target_id
   *   The new target ID.
   *
   * @returns $this
   */
  public function setTargetId($target_id);

  /**
   * Returns the relation URI.
   *
   * @returns string
   *   The relation URI.
   */
  public function getRelationUri();

  /**
   * Sets the relation URI.
   *
   * @param string $relation_uri
   *   The new relation URI.
   *
   * @returns $this
   */
  public function setRelationUri($relation_uri);

}
