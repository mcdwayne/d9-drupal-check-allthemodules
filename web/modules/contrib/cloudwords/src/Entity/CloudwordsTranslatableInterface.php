<?php

namespace Drupal\cloudwords\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Cloudwords translatable entities.
 *
 * @ingroup cloudwords
 */
interface CloudwordsTranslatableInterface extends  ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // @todo Add get/set methods for your configuration properties here.
  /**
   * Gets the Cloudwords translatable name.
   *
   * @return string
   *   Name of the Cloudwords translatable.
   */
  public function getName();

  /**
   * Sets the Cloudwords translatable name.
   *
   * @param string $name
   *   The Cloudwords translatable name.
   *
   * @return \Drupal\cloudwords\Entity\CloudwordsTranslatableInterface
   *   The called Cloudwords translatable entity.
   */
  public function setName($name);

  /**
   * Gets the Cloudwords translatable creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Cloudwords translatable.
   */
  public function getCreatedTime();

  /**
   * Sets the Cloudwords translatable creation timestamp.
   *
   * @param int $timestamp
   *   The Cloudwords translatable creation timestamp.
   *
   * @return \Drupal\cloudwords\Entity\CloudwordsTranslatableInterface
   *   The called Cloudwords translatable entity.
   */
  public function setCreatedTime($timestamp);

}
