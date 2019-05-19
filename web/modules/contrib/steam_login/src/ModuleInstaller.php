<?php

namespace Drupal\steam_login;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Extension\ModuleUninstallValidatorInterface;

/**
 * Module installer.
 */
class ModuleInstaller implements ModuleInstallerInterface {

  /**
   * Field storage config manager.
   *
   * @var \Drupal\field\FieldStorageConfigInterface
   */
  protected $fieldStorageConfigManager;

  /**
   * Field config manager.
   *
   * @var \Drupal\field\FieldConfigInterface
   */
  protected $fieldConfigManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entityt type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->fieldStorageConfigManager = $entity_type_manager->getStorage('field_storage_config');
    $this->fieldConfigManager = $entity_type_manager->getStorage('field_config');
  }

  /**
   * {@inheritdoc}
   */
  public function install(array $module_list, $enable_dependencies = TRUE) {
    $this->createSteamField();
    $this->createSteamUsernameField();
  }

  /**
   * {@inheritdoc}
   */
  public function uninstall(array $module_list, $uninstall_dependents = TRUE) {
  }

  /**
   * {@inheritdoc}
   */
  public function addUninstallValidator(ModuleUninstallValidatorInterface $uninstall_validator) {
  }

  /**
   * {@inheritdoc}
   */
  public function validateUninstall(array $module_list) {
  }

  /**
   * Create Steam field.
   */
  protected function createSteamField() {
    $this->createSteamFieldStorage();
    $this->createSteamFieldInstance();
  }

  /**
   * Create Steam field storage.
   */
  protected function createSteamFieldStorage() {
    try {
      $this->fieldStorageConfigManager->create([
        'field_name' => 'field_steam64id',
        'entity_type' => 'user',
        'type' => 'text',
        'cardinality' => -1,
      ])->save();
    }
    catch (\Exception $e) {
      if (PHP_SAPI === 'cli') {
        drush_print_r('Either the field_steam64id storage already exists or an error occured and it has not been created.');
      }
    }
  }

  /**
   * Create Steam field instance.
   */
  protected function createSteamFieldInstance() {
    try {
      $this->fieldConfigManager->create([
        'field_name' => 'field_steam64id',
        'entity_type' => 'user',
        'bundle' => 'user',
        'label' => 'The steam community ID',
      ])->save();
    }
    catch (\Exception $e) {
      if (PHP_SAPI === 'cli') {
        drush_print_r('Either the field_steam64id instance already exists or an error occured and it has not been created.');
      }
    }
  }

  /**
   * Create Steam username field.
   */
  protected function createSteamUsernameField() {
    $this->createSteamUsernameFieldStorage();
    $this->createSteamUsernameFieldInstance();
  }

  /**
   * Create Steam username field storage.
   */
  protected function createSteamUsernameFieldStorage() {
    try {
      $this->fieldStorageConfigManager->create([
        'field_name' => 'field_steam_username',
        'entity_type' => 'user',
        'type' => 'text',
        'cardinality' => -1,
      ])->save();
    }
    catch (\Exception $e) {
      if (PHP_SAPI === 'cli') {
        drush_print_r('Either the field_steam_username already exists or an error occured and it has not been created.');
      }
    }
  }

  /**
   * Create Steam username field instance.
   */
  protected function createSteamUsernameFieldInstance() {
    try {
      $this->fieldConfigManager->create([
        'field_name' => 'field_steam_username',
        'entity_type' => 'user',
        'bundle' => 'user',
        'label' => 'The steam user name',
      ])->save();
    }
    catch (\Exception $e) {
      if (PHP_SAPI === 'cli') {
        drush_print_r('Either the field_steam_username already exists or an error occured and it has not been created.');
      }
    }
  }

}
