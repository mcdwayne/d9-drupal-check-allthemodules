<?php

namespace Drupal\abstractpermissions\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

class PermissionsForm implements PermissionsFormInterface {

  use StringTranslationTrait;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * PermissionsForm constructor.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   */
  public function __construct(ModuleHandlerInterface $moduleHandler) {
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * Get a permisisons form.
   *
   * @see \Drupal\user\Form\UserPermissionsForm::buildForm
   *
   * @param $roleNames
   *   The role names, keyed by id.
   * @param $permissions
   *   The permissions info objects, must at least contain "title" key.
   * @param $permissionsPerRole
   *   An array of permission machine names, keyed by role id.
   * @return array
   */
  public function form($roleNames, $permissions, $permissionsPerRole) {
    $hide_descriptions = system_admin_compact_mode();
    $permissionsByProvider = $this->permissionsByProvider($permissions);

    $form = [
      '#type' => 'table',
      '#header' => [$this->t('Permission')],
      '#id' => 'permissions',
      '#attributes' => ['class' => ['permissions', 'js-permissions']],
      '#sticky' => TRUE,
    ];
    foreach ($roleNames as $roleName) {
      $form['#header'][] = [
        'data' => $roleName,
        'class' => ['checkbox'],
      ];
    }

    foreach ($permissionsByProvider as $provider => $permissions) {
      // Module name.
      $form["__$provider"] = [
        [
          '#wrapper_attributes' => [
            'colspan' => 2,
            'class' => ['module'],
            'id' => 'module-' . $provider,
          ],
          '#markup' => $this->moduleHandler->getName($provider),
        ],
      ];
      foreach ($permissions as $permissionName => $PermissionInfo) {
        // Fill in default values for the permission.
        $PermissionInfo += [
          'description' => '',
          'restrict access' => FALSE,
          'warning' => !empty($PermissionInfo['restrict access']) ? $this->t('Warning: Give to trusted roles only; this permission has security implications.') : '',
        ];
        $form[$permissionName]['description'] = [
          '#type' => 'inline_template',
          '#template' => '<div class="permission"><span class="title">{{ title }}</span>{% if description or warning %}<div class="description">{% if warning %}<em class="permission-warning">{{ warning }}</em> {% endif %}{{ description }}</div>{% endif %}</div>',
          '#context' => [
            'title' => $PermissionInfo['title'],
          ],
        ];
        // Show the permission description.
        if (!$hide_descriptions) {
          // @todo Prevent name clashes but keep compat with core.
          $form[$permissionName]['description']['#context']['description'] = $PermissionInfo['description'];
          $form[$permissionName]['description']['#context']['warning'] = $PermissionInfo['warning'];
        }
        foreach ($roleNames as $roleId => $roleName) {
          // Key by roleId to simplify submit.
          $permissionsPerRole += [$roleId => []];
          $form[$permissionName][$roleId] = [
            '#title' => $roleName . ': ' . $PermissionInfo['title'],
            '#title_display' => 'invisible',
            '#wrapper_attributes' => [
              'class' => ['checkbox'],
            ],
            '#type' => 'checkbox',
            '#default_value' => in_array($permissionName, $permissionsPerRole[$roleId]) ? 1 : 0,
          ];
        }
      }
    }
    $form['#attached']['library'][] = 'user/drupal.user.permissions';
    return $form;
  }

  /**
   * @param $permissions
   * @return array
   */
  protected function permissionsByProvider($permissions) {
    $permissionsByProvider = [];
    foreach ($permissions as $permissionName => $permissionInfo) {
      $permissionsByProvider[$permissionInfo['provider']][$permissionName] = $permissionInfo;
    }
    return $permissionsByProvider;
  }

  public function extractPermissionsByRole($values) {
    $permissionsByRole = [];
    // Ignore empty string that formState yields instead of empty array.
    if ($values) {
      foreach ($values as $permissionName => $permissionValue) {
        foreach ($permissionValue as $roleId => $value) {
          $permissionsByRole += [$roleId => []];
          if ($value) {
            $permissionsByRole[$roleId][] = $permissionName;
          }
        }
      }
    }
    return $permissionsByRole;
  }

}
