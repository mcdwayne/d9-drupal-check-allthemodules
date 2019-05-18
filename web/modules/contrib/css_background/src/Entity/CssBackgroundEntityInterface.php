<?php

namespace Drupal\css_background\Entity;

use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Image\ImageInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining CSS background entities.
 *
 * @ingroup css_background
 */
interface CssBackgroundEntityInterface extends RevisionableInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the CSS.
   *
   * @param bool $summary
   *   (optional, defaults to FALSE) Get summary CSS for a label.
   *
   * @return string
   *   Returns the Background CSS.
   */
  public function getCss($summary = FALSE);

  /**
   * Gets the CSS background type.
   *
   * @return string
   *   The CSS background type.
   */
  public function getType();

  /**
   * Gets the background image.
   *
   * @return \Drupal\file\FileInterface
   *   The background image.
   */
  public function getBgImage();

  /**
   * Sets the background image.
   *
   * @param \Drupal\Core\Image\ImageInterface $image
   *   The background image.
   *
   * @return \Drupal\css_background\Entity\CssBackgroundEntityInterface
   *   The called CSS background entity.
   */
  public function setBgImage(ImageInterface $image);

  /**
   * Gets the background color.
   *
   * @return string
   *   The background color.
   */
  public function getBgColor();

  /**
   * Sets the background color.
   *
   * @param string $color
   *   The background color.
   *
   * @return \Drupal\css_background\Entity\CssBackgroundEntityInterface
   *   The called CSS background entity.
   */
  public function setBgColor($color);

  /**
   * Gets the background extra properties.
   *
   * @return string
   *   The background extra properties.
   */
  public function getBgProperties();

  /**
   * Sets the background extra properties.
   *
   * @param string $properties
   *   The background extra properties.
   *
   * @return \Drupal\css_background\Entity\CssBackgroundEntityInterface
   *   The called CSS background entity.
   */
  public function setBgProperties($properties);

  /**
   * Gets the CSS background creation timestamp.
   *
   * @return int
   *   Creation timestamp of the CSS background.
   */
  public function getCreatedTime();

  /**
   * Sets the CSS background creation timestamp.
   *
   * @param int $timestamp
   *   The CSS background creation timestamp.
   *
   * @return \Drupal\css_background\Entity\CssBackgroundEntityInterface
   *   The called CSS background entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the CSS background published status indicator.
   *
   * Unpublished CSS background are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the CSS background is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a CSS background.
   *
   * @param bool $published
   *   TRUE to set this CSS background to published, FALSE to set unpublished.
   *
   * @return \Drupal\css_background\Entity\CssBackgroundEntityInterface
   *   The called CSS background entity.
   */
  public function setPublished($published);

  /**
   * Gets the CSS background revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the CSS background revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\css_background\Entity\CssBackgroundEntityInterface
   *   The called CSS background entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the CSS background revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the CSS background revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\css_background\Entity\CssBackgroundEntityInterface
   *   The called CSS background entity.
   */
  public function setRevisionUserId($uid);

}
