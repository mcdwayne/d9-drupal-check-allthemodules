<?php

namespace Drupal\registration_types\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Registration type entities.
 */
interface RegistrationTypeInterface extends ConfigEntityInterface {

  /**
   * Prefix to append to display modes used by registration types.
   */
  const DISPLAY_MODE_PREFIX = 'registration_type_';

  /**
   * Return registration type enabled flag.
   *
   * @return boolean
   *   Registration type enabled value.
   */
  public function getEnabled();

  /**
   * Set registration type enabled flag.
   *
   * @param boolean $enabled
   *
   * @return $this
   */
  public function setEnabled($enabled);

  /**
   * Return registration type custom path.
   *
   * @return string
   *   Registration type custom path.
   */
  public function getCustomPath();

  /**
   * Set registration type custom path.
   *
   * @param string $custom_path
   *
   * @return $this
   */
  public function setCustomPath($custom_path);

  /**
   * Return registration type tab title.
   *
   * @return string
   *   Registration type tab title.
   */
  public function getTabTitle();

  /**
   * Set registration type tab title.
   *
   * @param string $tab_title
   *
   * @return $this
   */
  public function setTabTitle($tab_title);

  /**
   * Return registration form display mode.
   *
   * @return string
   *   User form display mode machine_name.
   */
  public function getDisplay();

  /**
   * Set registration form display mode.
   *
   * @param string $display
   *
   * @return $this
   */
  public function setDisplay($display);

  /**
   * Return the user roles to assing at registration.
   *
   * @return array
   *   Array of Drupal user roles ids.
   */
  public function getRoles();

  /**
   * Set the user roles to assign at registration.
   *
   * @param array $roles
   *   Array of Drupal user roles ids.
   *
   * @return $this
   */
  public function setRoles($roles);

  /**
   * Return registration form page title.
   *
   * @return string
   *   User form page title.
   */
  public function getPageTitle();

  /**
   * Set registration form page title.
   *
   * @param string $page_title
   *
   * @return $this
   */
  public function setPageTitle($page_title);

  /**
   * Return registration type administrative description.
   *
   * @return string
   *   Registration type administrative description.
   */
  public function getDescription();

  /**
   * Set registration type administrative description.
   *
   * @param string $description
   *
   * @return $this
   */
  public function setDescription($description);

}
