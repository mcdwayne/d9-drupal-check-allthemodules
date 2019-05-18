<?php

namespace Drupal\packages;

use Drupal\packages\PackageState;
use Drupal\packages\PackageStorageException;
use Drupal\packages\Plugin\PackageManager;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;

/**
 * Class Packages.
 *
 * This is the primary service for interacting with packages and the state of
 * packages for each user. The package plugin manager should not be used
 * directly as this properly handles passing in the settings for the
 * package states.
 *
 * @package Drupal\packages
 */
class Packages implements PackagesInterface {

  /**
   * Package storage service.
   *
   * @var \Drupal\packages\PackageStorageInterface
   */
  protected $packageStorage;

  /**
   * Package plugin manager service.
   *
   * @var \Drupal\packages\Plugin\PackageManager
   */
  protected $packageManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The PackageState objects for the current user.
   *
   * @var array
   */
  protected $states;

  /**
   * Constructor.
   *
   * @param \Drupal\packages\PackageStorageInterface $package_storage
   *   The package storage service.
   * @param \Drupal\packages\Plugin\PackageManager $plugin_manager_package
   *   The package manager service.
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   The current user.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(PackageStorageInterface $package_storage, PackageManager $plugin_manager_package, AccountProxy $current_user, ModuleHandlerInterface $module_handler) {
    $this->packageStorage = $package_storage;
    $this->packageManager = $plugin_manager_package;
    $this->currentUser = $current_user;
    $this->moduleHandler = $module_handler;

    // Build the package states.
    $this->buildStates();
  }

  /**
   * {@inheritdoc}
   */
  public function buildStates() {
    // TODO: Can/should this be statically cached?
    // The storage service uses the current user, but let's make sure it hasn't
    // been changed. This is also useful during log in because it's possible that
    // the storage service is still set to the anonymous user.
    $this->packageStorage->setUserId($this->currentUser->id());

    // Load any package states from storage.
    $this->states = $this->packageStorage->load();

    // Load the package defintions.
    $package_definitions = $this->getPackageDefinitions();

    // Iterate all available packages.
    foreach ($package_definitions as $id => $definition) {
      // Check if this package is missing a state.
      if (!isset($this->states[$id])) {
        // Initialize a state.
        $state = new PackageState($id);
        $state->setEnabled($definition['enabled']);
        $state->setSettings($definition['default_settings']);
        $this->states[$id] = $state;
      }
      else {
        // Merge in any new default settings.
        $settings = array_merge($definition['default_settings'], $this->states[$id]->getSettings());
        $this->states[$id]->setSettings($settings);
      }

      // Check if the user is anonymous.
      if ($this->currentUser->isAnonymous()) {
        // Access is always denied for anonymous users.
        $access = FALSE;

        // Packages cannot be enabled for anonymous users.
        $this->states[$id]->disable();
      }
      else {
        // Recheck access, starting with the general package permission.
        if ($access = $this->currentUser->hasPermission('access packages')) {
          // Check if a package-specific permission is available.
          if (!empty($definition['permission'])) {
            // Check the additional permission.
            $access = $this->currentUser->hasPermission($definition['permission']);
          }
        }
      }

      // Set the package access.
      $this->states[$id]->setAccess($access);
    }

    // Iterate all available states.
    foreach ($this->states as $id => $state) {
      // Check if this package no longer exists.
      if (!isset($package_definitions[$id])) {
        // Remove the state.
        unset($this->states[$id]);
      }
    }

    // Allow other modules to alter the states.
    $this->moduleHandler->alter('packages_states', $this->states);
  }

  /**
   * {@inheritdoc}
   */
  public function getStates() {
    return $this->states;
  }

  /**
   * {@inheritdoc}
   */
  public function getState($package_id) {
    if (!isset($this->states[$package_id])) {
      throw new PluginNotFoundException($package_id);
    }
    return $this->states[$package_id];
  }

  /**
   * {@inheritdoc}
   */
  public function getPackage($package_id) {
    // Get the settings for this package.
    $settings = $this->getState($package_id)->getSettings();

    // Create an instance of the package plugin.
    return $this->packageManager->createInstance($package_id, $settings);
  }

  /**
   * {@inheritdoc}
   */
  public function getPackageDefinitions() {
    return $this->packageManager->getDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function saveStates() {
    $this->packageStorage->save($this->states);
  }

}
