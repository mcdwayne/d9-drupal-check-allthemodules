<?php

namespace Drupal\whitelabel;

use Drupal\file\FileInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\entity_reference_revisions\EntityNeedsSaveInterface;

/**
 * Provides an interface defining a white label entity.
 */
interface WhiteLabelInterface extends ContentEntityInterface, EntityOwnerInterface, EntityNeedsSaveInterface {

  /**
   * Gets a white label token.
   *
   * @return string
   *   The white label token.
   */
  public function getToken();

  /**
   * Set a white label token.
   *
   * @param string $token
   *   The white label token.
   *
   * @return $this
   */
  public function setToken($token);

  /**
   * Gets the white label site name visibility.
   *
   * @return string
   *   The white label site name visibility.
   */
  public function getNameDisplay();

  /**
   * Sets the white label site name visibility.
   *
   * @param string $value
   *   The white label site name visibility.
   *
   * @return $this
   */
  public function setNameDisplay($value);

  /**
   * Gets the white label site name.
   *
   * @return string
   *   The white label site name.
   */
  public function getName();

  /**
   * Sets the white label site name.
   *
   * @param string $name
   *   The white label site name.
   *
   * @return $this
   */
  public function setName($name);

  /**
   * Gets the white label slogan.
   *
   * @return string
   *   The white label slogan.
   */
  public function getSlogan();

  /**
   * Sets a white label slogan.
   *
   * @param string $slogan
   *   The white label slogan.
   *
   * @return $this
   */
  public function setSlogan($slogan);

  /**
   * Gets a white label logo.
   *
   * @return \Drupal\file\FileInterface
   *   The image file entity.
   */
  public function getLogo();

  /**
   * Sets a white label logo.
   *
   * @param \Drupal\file\FileInterface $file
   *   The image file entity.
   *
   * @return $this
   */
  public function setLogo(FileInterface $file);

  /**
   * Gets a white lable theme.
   *
   * @return string
   *   The system name of the theme.
   */
  public function getTheme();

  /**
   * Sets a white label theme.
   *
   * @param string $theme
   *   The system name of the theme.
   *
   * @return $this
   */
  public function setTheme($theme);

  /**
   * Gets a white label palette.
   *
   * @return array
   *   An array of hex color codes, keyed by theme region.
   */
  public function getPalette();

  /**
   * Sets a white label palette.
   *
   * @param array $palette
   *   An array of hex color codes, keyed by theme region.
   *
   * @return $this
   */
  public function setPalette(array $palette);

  /**
   * Gets the white label style sheets.
   *
   * @return array
   *   An array of styles heets.
   */
  public function getStylesheets();

  /**
   * Sets the white label style sheets.
   *
   * @param array $stylesheets
   *   An array of style sheets.
   *
   * @return $this
   */
  public function setStylesheets(array $stylesheets);

}
