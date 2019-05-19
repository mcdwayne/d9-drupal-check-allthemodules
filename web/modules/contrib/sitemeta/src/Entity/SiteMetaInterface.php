<?php

namespace Drupal\sitemeta\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Site meta entities.
 *
 * @ingroup sitemeta
 */
interface SiteMetaInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Site meta name.
   *
   * @return string
   *   Name of the Site meta.
   */
  public function getName();

  /**
   * Sets the Site meta name.
   *
   * @param string $name
   *   The Site meta name.
   *
   * @return \Drupal\sitemeta\Entity\SiteMetaInterface
   *   The called Site meta entity.
   */
  public function setName($name);

  /**
   * Gets the Site meta creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Site meta.
   */
  public function getCreatedTime();

  /**
   * Sets the Site meta creation timestamp.
   *
   * @param int $timestamp
   *   The Site meta creation timestamp.
   *
   * @return \Drupal\sitemeta\Entity\SiteMetaInterface
   *   The called Site meta entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Site meta published status indicator.
   *
   * Unpublished Site meta are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Site meta is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Site meta.
   *
   * @param bool $published
   *   TRUE to set this Site meta to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\sitemeta\Entity\SiteMetaInterface
   *   The called Site meta entity.
   */
  public function setPublished($published);

  /**
   * Returns the Site meta path.
   *
   * @return string
   *   Returns an internal path.
   */
  public function getPath();

  /**
   * Sets the Site meta path.
   *
   * @param string $path
   *   Internal path.
   *
   * @return \Drupal\sitemeta\Entity\SiteMetaInterface
   *   The called Site meta entity.
   */
  public function setPath($path);

  /**
   * Returns the Site meta description.
   *
   * @return string
   *   Returns the Site meta description.
   */
  public function getDescription();

  /**
   * Sets the Site meta description.
   *
   * @param string $description
   *   Site meta description.
   *
   * @return \Drupal\sitemeta\Entity\SiteMetaInterface
   *   The called Site meta entity.
   */
  public function setDescription($description);

  /**
   * Returns the Site meta keywords.
   *
   * @return string
   *   Returns the Site meta keywords.
   */
  public function getKeywords();

  /**
   * Sets the Site meta keywords.
   *
   * @param string $keywords
   *   Site meta keywords.
   *
   * @return \Drupal\sitemeta\Entity\SiteMetaInterface
   *   The called Site meta entity.
   */
  public function setKeywords($keywords);

  /**
   * Returns the Site meta langcode.
   *
   * @return string
   *   Returns the Site meta langcode.
   */
  public function getLangcode();

  /**
   * Sets the Site meta langcode.
   *
   * @param string $langcode
   *   Site meta langcode..
   *
   * @return \Drupal\sitemeta\Entity\SiteMetaInterface
   *   The called Site meta entity.
   */
  public function setLangcode($langcode);

}
