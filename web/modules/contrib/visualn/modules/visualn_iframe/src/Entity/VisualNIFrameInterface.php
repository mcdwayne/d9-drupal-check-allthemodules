<?php

namespace Drupal\visualn_iframe\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining VisualN IFrame entities.
 *
 * @ingroup iframes_toolkit
 */
interface VisualNIFrameInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the VisualN IFrame name.
   *
   * @return string
   *   Name of the VisualN IFrame.
   */
  public function getName();

  /**
   * Sets the VisualN IFrame name.
   *
   * @param string $name
   *   The VisualN IFrame name.
   *
   * @return \Drupal\visualn_iframe\Entity\VisualNIFrameInterface
   *   The called VisualN IFrame entity.
   */
  public function setName($name);

  /**
   * Gets the VisualN IFrame creation timestamp.
   *
   * @return int
   *   Creation timestamp of the VisualN IFrame.
   */
  public function getCreatedTime();

  /**
   * Sets the VisualN IFrame creation timestamp.
   *
   * @param int $timestamp
   *   The VisualN IFrame creation timestamp.
   *
   * @return \Drupal\visualn_iframe\Entity\VisualNIFrameInterface
   *   The called VisualN IFrame entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the VisualN IFrame published status indicator.
   *
   * Unpublished VisualN IFrame are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the VisualN IFrame is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a VisualN IFrame.
   *
   * @param bool $published
   *   TRUE to set this VisualN IFrame to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\visualn_iframe\Entity\VisualNIFrameInterface
   *   The called VisualN IFrame entity.
   */
  public function setPublished($published);

  // @todo: add docblock
  public function getDrawingId();
  public function setDrawingId($drawing_id);
  public function getHash();
  public function setHash($hash);
  public function getSettings();
  public function setSettings($settigs);
  public function getData();
  public function setData($data);

}
