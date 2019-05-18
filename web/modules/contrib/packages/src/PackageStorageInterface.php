<?php

namespace Drupal\packages;

/**
 * Interface PackageStorageInterface.
 *
 * @package Drupal\packages
 */
interface PackageStorageInterface {

  /**
   * Set the user Id to be used for storage operations.
   *
   * @param int $user_id
   *   The user Id to set or NULL if the current user should be used.
   *
   * @return \Drupal\packages\PackageStorageInterface
   *   The called class.
   */
  public function setUserId($user_id = NULL);

  /**
   * Get the user Id being used for storage.
   *
   * @return int
   *   The set user Id.
   */
  public function getUserId();

  /**
   * Delete the package data for the set user.
   */
  public function delete();

  /**
   * Load the package state data for the set user.
   *
   * @return array
   *   An array of \Drupal\packages\PackageState objects.
   */
  public function load();

  /**
   * Save the package states data for the set user.
   *
   * @param array $states
   *   An array of \Drupal\packages\PackageState objects.
   */
  public function save(array $states);

}
