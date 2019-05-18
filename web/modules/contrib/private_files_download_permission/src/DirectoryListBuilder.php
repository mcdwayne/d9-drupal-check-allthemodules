<?php

namespace Drupal\pfdp;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Link;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Defines a class to build a list of Private files download permission
 * directory entities.
 */
class DirectoryListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function render() {
    $settings = \Drupal::config('pfdp.settings');
    // Prepare the information render array.
    $information = [];
    $information['margin'] = [
      '#markup' => '<p />',
    ];
    // Check if the file system download method is set to private.
    if ('private' !== file_default_scheme()) {
      drupal_set_message($this->t('Your @default_download_method is not set to private. Please keep in mind that these settings only affect private file system downloads.', ['@default_download_method' => Link::fromTextAndUrl('default download method', Url::fromRoute('system.file_system_settings'))->toString()]), 'warning');
    }
    // Check the private file system path.
    $private_file_system_path = Settings::get('file_private_path');
    if (!$private_file_system_path) {
      drupal_set_message($this->t('Your private file system path is not set.'), 'warning');
    }
    else {
      $information['private_file_system_path'] = [
        '#markup' => '<p>' . $this->t('Your private file system path is set to %path.', ['%path' => $private_file_system_path]) . '</p>',
      ];
    }
    // Check if by-user checks are enabled.
    if (!$settings->get('by_user_checks')) {
      $information['by_user_checks'] = [
        '#markup' => '<p>' . $this->t('@by_user_checks are not enabled.', ['@by_user_checks' => Link::fromTextAndUrl('By-user checks', Url::fromRoute('pfdp.settings'))->toString()]) . '</p>',
      ];
    }
    // Return the render array.
    return $information + parent::render();
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    // Prepare the table header.
    $header = [];
    $header['id'] = $this->t('Id');
    $header['path'] = $this->t('Directory path');
    $header['bypass'] = $this->t('Bypass');
    $header['users'] = $this->t('Enabled users');
    $header['roles'] = $this->t('Enabled roles');
    // Return the table header.
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $pfdp_directory = $entity;
    // Prepare the table row for the directory.
    $row = [];
    $row['id'] = $pfdp_directory->id();
    $row['path'] = $pfdp_directory->path;
    $row['bypass'] = $pfdp_directory->bypass ? $this->t('Yes') : '';
    $row['users'] = implode(', ', array_map(function ($uid) {
      $user = User::load($uid);
      return $user ? $user->label() : NULL;
    }, pfdp_get_proper_user_array($pfdp_directory->users)));
    if ($pfdp_directory->grant_file_owners) {
      $row['users'] = $this->t('File owners') . ', ' . $row['users'];
    }
    if (', ' == Unicode::substr($row['users'], -2, 2)) {
      $row['users'] = Unicode::substr($row['users'], 0, -2);
    }
    $row['roles'] = implode(', ', array_map(function ($rid) {
      $role = Role::load($rid);
      return $role ? $role->label() : NULL;
    }, $pfdp_directory->roles));
    if (', ' == Unicode::substr($row['roles'], -2, 2)) {
      $row['roles'] = Unicode::substr($row['roles'], 0, -2);
    }
    // Return the table row.
    return $row + parent::buildRow($pfdp_directory);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $pfdp_directory = $entity;
    // Prepare the table row operations for the directory.
    $operations = parent::getDefaultOperations($pfdp_directory);
    if ($pfdp_directory->hasLinkTemplate('edit')) {
      $operations['edit'] = [
        'title' => t('Edit directory'),
        'weight' => 20,
        'url' => $pfdp_directory->urlInfo('edit'),
      ];
    }
    if ($pfdp_directory->hasLinkTemplate('delete')) {
      $operations['delete'] = [
        'title' => t('Delete directory'),
        'weight' => 40,
        'url' => $pfdp_directory->urlInfo('delete'),
      ];
    }
    // Return the table row operations.
    return $operations;
  }

}
