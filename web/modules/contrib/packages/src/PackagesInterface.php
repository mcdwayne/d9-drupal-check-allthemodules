<?php

namespace Drupal\packages;

/**
 * Interface PackagesInterface.
 *
 * @package Drupal\packages
 */
interface PackagesInterface {

  /**
   * Build the package states for the current user using both the database
   * storage as well as package defaults, if needed.
   *
   * This should be called during construction but could also be used to wipeout
   * any pending state changes.
   */
  public function buildStates();

  /**
   * Return the package states.
   *
   * @return array
   *   An array of PackageState objects.
   */
  public function getStates();

  /**
   * Get the state of a given package.
   *
   * @param string $package_id
   *   The package Id.
   *
   * @return \Drupal\packages\PackageState
   *   The PackageState for the given package.
   */
  public function getState($package_id);

  /**
   * Get a package plugin.
   *
   * @param string $package_id
   *   The package Id.
   *
   * @return \Drupal\packages\Plugin\PackageInterface
   *   The PackageState for the given package.
   */
  public function getPackage($package_id);

  /**
   * Get all package plugin definitions.
   *
   * @return array
   *   An array of package plugin definitions.
   */
  public function getPackageDefinitions();

  /**
   * Save the states for the current user.
   */
  public function saveStates();

}
