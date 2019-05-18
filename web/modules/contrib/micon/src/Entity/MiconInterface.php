<?php

namespace Drupal\micon\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Micon entities.
 */
interface MiconInterface extends ConfigEntityInterface {

  /**
   * Gets the type of package.
   *
   * @return string
   *   Either 'font' or 'image'.
   */
  public function type();

  /**
   * Get Micon package information.
   *
   * @return array
   *   The information for the IcoMoon package.
   */
  public function getInfo();

  /**
   * Get unique IcoMoon package name.
   */
  public function getName();

  /**
   * Get unique IcoMoon package prefix.
   */
  public function getPrefix();

  /**
   * Get Micon package icons with tag as key.
   *
   * @return array
   *   The information for the IcoMoon icons.
   */
  public function getIcons();

  /**
   * Set the archive as base64 encoded string.
   *
   * @param string $zip_path
   *   The URI of the zip file.
   *
   * @return $this
   */
  public function setArchive($zip_path);

  /**
   * Gets the archive from a base64 encoded string.
   *
   * @return string
   *   The restored archive data.
   */
  public function getArchive();

  /**
   * Return the stylesheet of the Micon package if it exists.
   *
   * @return string
   *   The path to the IcoMoon style.css file.
   */
  public function getStylesheet();

  /**
   * Load all Micon packages.
   *
   * @return static[]
   *   An array of entity objects indexed by their IDs.
   */
  public static function loadActive();

  /**
   * Load all active Micon IDs.
   *
   * @return static[]
   *   An array of entity IDs indexed by their IDs.
   */
  public static function loadActiveIds();

  /**
   * Load all active Micon labels.
   *
   * @return static[]
   *   An array of entity labels indexed by their IDs.
   */
  public static function loadActiveLabels();

}
