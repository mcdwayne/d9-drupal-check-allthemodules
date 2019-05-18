<?php

namespace Drupal\permission_matrix\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\PermissionHandlerInterface;
use Drupal\user\RoleStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PermissionMatrixConfiguration.
 */
class PermissionMatrix extends ConfigFormBase {

  /**
   * The permission handler.
   *
   * @var \Drupal\user\PermissionHandlerInterface
   */
  protected $permissionHandler;

  /**
   * The role storage.
   *
   * @var \Drupal\user\RoleStorageInterface
   */
  protected $roleStorage;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new UserPermissionsForm.
   *
   * @param \Drupal\user\PermissionHandlerInterface $permission_handler
   *   The permission handler.
   * @param \Drupal\user\RoleStorageInterface $role_storage
   *   The role storage.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(PermissionHandlerInterface $permission_handler, RoleStorageInterface $role_storage, ModuleHandlerInterface $module_handler) {
    $this->permissionHandler = $permission_handler;
    $this->roleStorage = $role_storage;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.permissions'),
      $container->get('entity.manager')->getStorage('user_role'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'permission_matrix_customization.permissionmatrixcustomization',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'permission_matrix_customization';
  }

  /**
   * Gets the roles to display in this form.
   *
   * @return \Drupal\user\RoleInterface[]
   *   An array of role objects.
   */
  protected function getRoles() {
    return $this->roleStorage->loadMultiple();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $role_names = [];
    $role_permissions = [];
    $admin_roles = [];

    foreach ($this->getRoles() as $role_name => $role) {
      // Retrieve role names for columns.
      $role_names[$role_name] = $role->label();
      // Fetch permissions for the roles.
      $role_permissions[$role_name] = $role->getPermissions();
      $admin_roles[$role_name] = $role->isAdmin();
    }

    // Store $role_names for use when saving the data.
    $form['role_names'] = [
      '#type' => 'value',
      '#value' => $role_names,
    ];

    $form['permissions'] = [
      '#type' => 'table',
      '#header' => [$this->t('Permission')],
      '#id' => 'permissions',
      '#attributes' => ['class' => ['permissions', 'js-permissions']],
      '#sticky' => FALSE,
    ];
    foreach ($role_names as $name) {
      $form['permissions']['#header'][] = [
        'data' => $name,
        'class' => ['checkbox'],
      ];
    }

    $permissions = $this->permissionHandler->getPermissions();
    $permissions_by_provider = [];

    $permissions = $this->getMatrixPermissions();

    foreach ($permissions as $permission => $permission_label) {
      $permission_for_group = $this->getGroupPermission($permission, 1);
      $grouppermissions = array_keys($permission_for_group);

      $form['permissions'][$permission]['description'] = [
        '#type' => 'inline_template',
        '#template' => '<div class="permission"><span class="title" alt="{{ description }}" title="{{ description }}">{{ title }}</span></div>',
        '#context' => [
          'title' => $permission_label,
          'description' => implode(", ", $grouppermissions),
        ],
      ];

      foreach ($role_names as $rid => $name) {

        if (!empty($permission_for_group)) {
          $default_check = empty(array_diff($grouppermissions, $role_permissions[$rid])) ? 1 : 0;
        }
        else {
          $default_check = in_array($permission, $role_permissions[$rid]) ? 1 : 0;
        }

        $form['permissions'][$permission][$rid] = [
          '#title' => $name . ': ' . $permission,
          '#title_display' => 'invisible',
          '#wrapper_attributes' => [
            'class' => ['checkbox'],
          ],
          '#type' => 'checkbox',
          '#default_value' => $default_check,
          '#attributes' => ['class' => ['rid-' . $rid, 'js-rid-' . $rid]],
          '#parents' => [$rid, $permission],
        ];
        // Show a column of disabled but checked checkboxes.
        if ($admin_roles[$rid]) {
          $form['permissions'][$permission][$rid]['#disabled'] = TRUE;
          $form['permissions'][$permission][$rid]['#default_value'] = TRUE;
        }
      }
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save permissions'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $role_wise_perm = [];

    foreach ($form_state->getValue('role_names') as $role_name => $name) {
      $role_permission = (array) $form_state->getValue($role_name);
      foreach ($role_permission as $role_key => $role_perm) {
        $permission_from_group = $this->getGroupPermission($role_key, $role_perm);
        if (!empty($permission_from_group)) {
          unset($role_permission[$role_key]);
          $role_wise_perm = array_merge($role_wise_perm, $permission_from_group);
        }

      }

      $role_permission = array_merge($role_wise_perm, $role_permission);
      user_role_change_permissions($role_name, $role_permission);
    }

    $this->messenger()->addStatus($this->t('The changes have been saved.'));
  }

  /**
   * Get permission from group.
   */
  private function getGroupPermission($group, $role_perms) {
    $ent_perm = [];
    $entity = \Drupal::entityTypeManager()
      ->getStorage('permission_group')
      ->load($group);
    if ($entity) {
      $entity_permissions = $entity->get('permissions');

      foreach ($entity_permissions as $ek => $ev) {
        if ($ev <> "") {
          $ent_perm[$ev] = $role_perms;
        }
      }
    }
    return $ent_perm;
  }

  /**
   * Custom function to get custommized permission.
   */
  private function getMatrixPermissions() {
    $permission_config = $this->config('permission_matrix.config')->get('permission_matrix_config');
    $result = json_decode($permission_config);
    $perm = [];
    $avoid = [];
    // Get all permission groups.
    $entities = \Drupal::entityTypeManager()
      ->getStorage('permission_group')
      ->loadMultiple();
    foreach ($entities as $key => $val) {
      $entity_permissions = $val->get('permissions');

      foreach ($entity_permissions as $ek => $ev) {
        if ($ev <> "") {
          $avoid[] = $ev;
        }
      }

      $perm[$val->id()] = $val->label();
    }
    // Get individual permissions.
    foreach ($result as $k => $v) {
      if (!in_array($v->permission, $avoid)) {
        $perm[$v->permission] = $v->permission_label;
      }
    }

    return $perm;
  }

}
