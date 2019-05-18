<?php

namespace Drupal\abstractpermissions\FormAlter;

use Drupal\abstractpermissions\PermissionGovernor;

abstract class PermissionsFormAlterBase {

  public static function alterForm(array &$form) {
    /** @var \Drupal\abstractpermissions\AbstractPermissionsServiceInterface $abstractPermissionsService */
    $abstractPermissionsService = \Drupal::service('abstractpermissions.service');
    $permissionGraph = $abstractPermissionsService->getPermissionGraph();

    $moduleName = [];
    $ungovernedModulePermissions = [];
    $governedModulePermissions = [];
    foreach ($form as $permissionName => &$element) {
      if (substr($permissionName, 0, 1) === '#') {
        continue;
      }
      $isModuleRow = isset($element[0]['#wrapper_attributes']['colspan']);
      if ($isModuleRow) {
        $moduleName = $permissionName;
        $ungovernedModulePermissions[$moduleName] = [];
        $governedModulePermissions[$moduleName] = [];
        continue;
      }
      // We only need to know if this permission is governed or not.
      $governor = $permissionGraph->getGovernor($permissionName);
      if ($governor) {
        static::governedPermission($form[$permissionName], $governor);
        $governedModulePermissions[$moduleName][] = $permissionName;
      }
      else {
        static::ungovernedPermission($form[$permissionName]);
        $ungovernedModulePermissions[$moduleName][] = $permissionName;
      }
    }
    foreach ($ungovernedModulePermissions as $moduleName => $permissions) {
      if (!$permissions) {
        static::governedModule($form[$moduleName]);
      }
    }
    foreach ($governedModulePermissions as $moduleName => $permissions) {
      if (!$permissions) {
        static::ungovernedModule($form[$moduleName]);
      }
    }
    $form += ['#empty' => t('No permissions to show here.')];
    $form['#attached']['library'][] = 'abstractpermissions/permissions-form';
  }

  protected static function ungovernedModule(array &$row) {
    $row['#attributes']['class'][] = 'abstractpermissions-module-is-ungoverned';
  }

  protected static function ungovernedPermission(array &$row) {
    $row['#attributes']['class'][] = 'abstractpermissions-module-is-ungoverned';
  }

  protected static function governedModule(array &$row) {
    $row['#attributes']['class'][] = 'abstractpermissions-permission-is-governed';
  }

  protected static function governedPermission(array &$row, PermissionGovernor $governor) {
    $row['#attributes']['class'][] = 'abstractpermissions-permission-is-governed';
    $row['description'] = [$row['description']];
    foreach ($governor->getPermissionAbstractions() as $paId => $permissionAbstraction) {
      /** @noinspection PhpUnhandledExceptionInspection*/
      $row['description'][$paId] = [
        '#type' => 'link',
        '#title' => '*',
        '#url' => $permissionAbstraction->toUrl(),
        '#attributes' => [
          'target' => '_blank',
          'title' => $permissionAbstraction->label(),
        ],
      ];
    }
  }

}
