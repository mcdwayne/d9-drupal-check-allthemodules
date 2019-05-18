<?php

namespace Drupal\backup_permissions;

use Drupal\user\Entity\Role;
use Drupal\Component\Utility\Html;

/**
 * Helper Storage class.
 */
trait BackupPermissionsStorageTrait {

  /**
   * Save an entry in the database.
   *
   * @param array $entry
   *   An array containing all the fields of the database record.
   *
   * @return int
   *   The number of updated rows.
   */
  public function insert(array $entry) {
    $return_value = NULL;
    $db = \Drupal::database();
    $return_value = $db->insert('backup_permissions')
      ->fields($entry)
      ->execute();

    return $return_value;
  }

  /**
   * Delete an entry from the database.
   *
   * @param int $bid
   *   Id of the backup.
   */
  public function delete($bid) {
    $db = \Drupal::database();
    $db->delete('backup_permissions')
      ->condition('id', $bid)
      ->execute();
  }

  /**
   * Loads an entry from the database.
   *
   * @param array $entry
   *   An array containing at least the backup identifier 'pid' element of the
   *   entry to delete.
   * @param int $limit
   *   Range of the query.
   */
  public function load(array $entry = array(), $limit = '') {
    $db = \Drupal::database();
    $select = $db->select('backup_permissions', 'bp');
    $select->fields('bp');
    $select->orderBy("created", "DESC");

    // Add each field and value as a condition to this query.
    foreach ($entry as $field => $value) {
      $select->condition($field, $value);
    }

    if (!empty($limit) && is_numeric($limit)) {
      $select->range(0, $limit);
    }

    // Return the result in object format.
    return $select->execute()->fetchAll();
  }

  /**
   * Function to update permissions by passed data.
   *
   * @param array $roles
   *   Roles to process permissions for.
   * @param array $rows
   *   Permission states respective to roles.
   * @param string $status
   *   Permission state to be updated.
   */
  public function resetRoles(array $roles, array $rows, $status) {
    $is_updated = array();
    $message = t('No roles/permissions updated.');
    foreach ($rows as $row) {
      foreach ($roles as $role_name) {
        $role = Role::load($role_name);
        if (is_object($role) && count($role) && $role->id()) {
          $is_updated[$role->id()] = $role->label();
          $permission = $this->validatePermission($row['name']);
          $permission_status = $row[$role->id()];

          if ($permission_status == 'Yes' && ($status == 0 || $status == 1)) {
            $role->grantPermission($permission);
          }
          elseif ($permission_status == 'No' && ($status == 0 || $status == 2)) {
            $role->revokePermission($permission);
          }
          $role->save();
        }
        else {
          // Your data array.
          $data = array('id' => $role_name, 'label' => ucfirst($role_name));
          // Creating your role.
          $role = Role::create($data);
          // Saving your role.
          $role->save();
          $role = Role::load($role_name);
          if ($role->id()) {
            $is_updated[$role->id()] = $role->label();
            $permission = $this->validatePermission($row['name']);

            $permission_status = $row[$role_name];

            if ($permission_status == 'Yes' && ($status == 0 || $status == 1)) {
              $role->grantPermission($permission);
            }
            elseif ($permission_status == 'No' && ($status == 0 || $status == 2)) {
              $role->revokePermission($permission);
            }
          }
        }
      }
    }
    drupal_flush_all_caches();

    if (count($is_updated)) {
      $message = t("The role(s) @roles permissions has has been updated successfully.", array('@roles' => implode(", ", $is_updated)));
    }

    drupal_set_message(Html::escape($message));
  }

  /**
   * Use to validate the permissions.
   *
   * @param string $permission_name
   *   Permission to be validated.
   *
   * @return array
   *   An array of role permissions.
   */
  public function validatePermission($permission_name) {
    $permissions = '';
    // Get the all the permissions having module name.
    $permission_handler = \Drupal::service('user.permissions');
    $permissions = $permission_handler->getPermissions();
    if (array_key_exists($permission_name, $permissions)) {
      $permission = $permission_name;
    }
    return $permission;
  }

  /**
   * Returns list of available backups.
   *
   * @return array
   *   An array of backups.
   */
  public function getBackupList() {
    $db = \Drupal::database();
    $select = $db->select('backup_permissions', 'bp');
    $select->fields('bp');
    $select->orderBy("created", "DESC");

    $pager = $select->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->limit(25);
    $results = $pager->execute();

    return $results;
  }

}
