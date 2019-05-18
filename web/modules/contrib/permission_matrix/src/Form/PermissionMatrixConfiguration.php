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
class PermissionMatrixConfiguration extends ConfigFormBase {

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
      'permission_matrix.config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'permission_matrix_configuration';
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

    $form['permissions'] = [
      '#type' => 'table',
      '#header' => [],
      '#id' => 'permissions',
      '#attributes' => ['class' => ['permissions', 'js-permissions']],
      '#sticky' => FALSE,
    ];

    $form['permissions']['#header'][] = [
      'data' => 'Select',
    ];
    $form['permissions']['#header'][] = [
      'data' => 'Permission',
    ];

    $permissions = $this->permissionHandler->getPermissions();
    $permissions_by_provider = [];
    $saved_permission = $this->getMatrixPermissions();
    if (isset($saved_permission)) {
      $permission_keys = array_keys($saved_permission);
    }

    $i = 0;
    foreach ($permissions as $permission_name => $permission) {
      $i++;
      $permission_title = $permission['title'];
      $form['permissions'][$i][$permission_name] = [
        '#type' => 'checkbox',
        '#default_value' => in_array($permission_name, $permission_keys),
      ];
      $form['permissions'][$i]['label'] = [
        '#type' => 'textfield',
        '#default_value' => ($saved_permission[$permission_name]) ? $saved_permission[$permission_name] : $permission_title,
        '#attributes' => [
          'placeholder' => $permission_title,
        ],
      ];
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $permission_array = [];
    foreach ($form_state->getValue('permissions') as $key => $value) {
      foreach ($value as $k => $v) {
        if ($k == 'label') {
          $label = $v;
        }
        else {
          $permission = $k;
          $permission_on = $v;
        }
      }
      if ($permission_on == 1) {
        $permission_array[] = [
          'permission' => $permission,
          'permission_label' => $label,
        ];
      }
    }

    $this->insertPermissionMatrix($permission_array);
    $this->messenger()->addStatus($this->t('The changes have been saved.'));
  }

  /**
   * Custom function to save permission in custom table.
   */
  private function insertPermissionMatrix($permission_array) {
    $this->config('permission_matrix.config')
      ->set('permission_matrix_config', json_encode($permission_array))
      ->save();
  }

  /**
   * Custom function to get custommized permission.
   */
  private function getMatrixPermissions() {
    $result_arr = [];
    $permission_config = $this->config('permission_matrix.config')->get('permission_matrix_config');
    $result = json_decode($permission_config);
    if ($result) {
      foreach ($result as $val) {
        $result_arr[$val->permission] = $val->permission_label;
      }
    }
    return $result_arr;
  }

}
