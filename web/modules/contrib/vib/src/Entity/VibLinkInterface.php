<?php

namespace Drupal\vib\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface for the vib link entity.
 */
interface VibLinkInterface extends ContentEntityInterface {

  /**
   * Gets the token.
   *
   * @return string
   *   The token.
   */
  public function getToken();

  /**
   * Gets the timestamp the entity will be deleted.
   *
   * @return int
   *   The timestamp.
   */
  public function getDeletedTime();

  /**
   * Sets the timestamp the entity will be deleted.
   *
   * @param int $timestamp
   *   The vib_link deletion timestamp.
   *
   * @return \Drupal\vib\Entity\VibLinkInterface
   *   The called vib_link entity.
   */
  public function setDeletedTime($timestamp);

  /**
   * Gets the email body content.
   *
   * @return string
   *   The body content.
   */
  public function getEmailContent();

  /**
   * Sets the email body content.
   *
   * @param string $text
   *   The vib_link deletion timestamp.
   *
   * @return \Drupal\vib\Entity\VibLinkInterface
   *   The called vib_link entity.
   */
  public function setEmailContent($text);

  /**
   * Gets the library.
   *
   * @return string
   *   The library name.
   */
  public function getLibrary();

  /**
   * Sets the library.
   *
   * @param string $name
   *   The library name.
   *
   * @return \Drupal\vib\Entity\VibLinkInterface
   *   The called vib_link entity.
   */
  public function setLibrary($name);

}
