<?php

/**
 * @file
 * Contains \Drupal\log\LogPermissions.
 */

namespace Drupal\log;

use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\log\Entity\LogType;

/**
 * Provides dynamic permissions for logs of different types.
 */
class LogPermissions {

  use StringTranslationTrait;
  use UrlGeneratorTrait;

  /**
   * Returns an array of log type permissions.
   *
   * @return array
   *   The log type permissions.
   *   @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function logTypePermissions() {
    $perms = array();
    // Generate log permissions for all log types.
    foreach (LogType::loadMultiple() as $type) {
      $perms += $this->buildPermissions($type);
    }

    return $perms;
  }

  /**
   * Returns a list of log permissions for a given log type.
   *
   * @param \Drupal\log\Entity\LogType $type
   *   The log type.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(LogType $type) {
    $type_id = $type->id();
    $type_params = array('%type_name' => $type->label());
    $ops = array('view', 'edit', 'delete');
    $scopes = array('any', 'own');

    $permissions = [];
    $permissions["create $type_id log entities"] = [
      'title' => $this->t('%type_name: Create new log entities', $type_params),
    ];
    foreach ($ops as $op) {
      foreach ($scopes as $scope) {
        $scope_params = $type_params + ['%scope' => $scope, '%op' => ucfirst($op)];
        $permissions["$op $scope $type_id log entities"] = [
          'title' => $this->t('%type_name: %op %scope log entities', $scope_params),
        ];
      }
    }
    $permissions["view $type_id revisions"] = [
      'title' => $this->t('%type_name: View log revisions', $type_params),
    ];
    $permissions["revert $type_id revisions"] = [
      'title' => $this->t('%type_name: Revert log revisions', $type_params),
    ];
    $permissions["delete $type_id revisions"] = [
      'title' => $this->t('%type_name: Delete log revisions', $type_params),
    ];
    return $permissions;
  }

}
