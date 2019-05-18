<?php
/**
 * @file
 * Contains \Drupal\collect\CollectContainerInterface
 */

namespace Drupal\collect;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface for the generic data container entity.
 */
interface CollectContainerInterface extends ContentEntityInterface {

  /**
   * Gets the date of the data.
   *
   * The date is the date of an event in the lifecycle of the origin data.
   *
   * @return int
   *   The timestamp.
   */
  public function getDate();

  /**
   * Sets the date of the data.
   *
   * @param int $date
   *   The date timestamp.
   *
   * @see CollectContainerInterface::getDate()
   */
  public function setDate($date);

  /**
   * Gets the data.
   *
   * @return mixed
   *   The data.
   */
  public function getData();

  /**
   * Sets the MIME type.
   *
   * @param string $type
   *   The MIME type.
   */
  public function setType($type);

  /**
   * Sets the schema URI.
   *
   * @param string $schema_uri
   *   The schema URI.
   */
  public function setSchemaUri($schema_uri);

  /**
   * Gets the MIME type.
   *
   * @return string
   *   The MIME type.
   */
  public function getType();

  /**
   * Sets the origin URI.
   *
   * @param string $origin_uri
   *   The origin URI.
   */
  public function setOriginUri($origin_uri);

  /**
   * Gets the origin URI.
   *
   * @return string
   *   The origin URI.
   */
  public function getOriginUri();

  /**
   * Gets the schema URI.
   *
   * @return string
   *   The schema URI.
   */
  public function getSchemaUri();

  /**
   * Sets the data.
   *
   * @param mixed $data
   *   The data.
   */
  public function setData($data);
}
