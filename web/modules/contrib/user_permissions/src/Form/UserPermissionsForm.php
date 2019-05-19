<?php

namespace Drupal\user_permissions\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Drupal\user\Form\UserPermissionsRoleSpecificForm;
use Drupal\user\PermissionHandlerInterface;
use Drupal\user\RoleStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * User permissions form for granting permissions to individual users.
 */
class UserPermissionsForm extends UserPermissionsRoleSpecificForm {

  /**
   * Current user from current session.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Current user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * Constructs a new UserPermissionsForm.
   *
   * @param \Drupal\user\PermissionHandlerInterface $permission_handler
   *   The permission handler.
   * @param \Drupal\user\RoleStorageInterface $role_storage
   *   The role storage.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user from current session.
   */
  public function __construct(PermissionHandlerInterface $permission_handler, RoleStorageInterface $role_storage, ModuleHandlerInterface $module_handler, AccountInterface $account) {
    parent::__construct($permission_handler, $role_storage, $module_handler);
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.permissions'),
      $container->get('entity.manager')->getStorage('user_role'),
      $container->get('module_handler'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $user = NULL) {
    if (!$this->account->hasPermission('administer permissions')) {
      return new RedirectResponse(Url::fromRoute('user.page'));
    }

    if (!is_null($user)) {
      $uid = $user;
      $this->user = User::load($uid);
    }
    else {
      $form['user'] = [
        '#markup' => $this->t('User could not be found.'),
      ];
      return $form;
    }

    // Set user specific role name.
    $role_name = '_user_role_' . $uid;
    // Check for the existence of this role.
    $role = Role::load($role_name);

    if ($role) {
      // If role exists, use this for the UserPermissionsRoleSpecificForm.
      $this->userRole = Role::load($role_name);
      $form = parent::buildForm($form, $form_state, $this->userRole);
    }
    else {
      // If role does not exists,
      // load the dummy role and use it to define base permissions.
      $this->userRole = Role::load(USER_PERMISSIONS_NO_ROLE);
      $form = parent::buildForm($form, $form_state, $this->userRole);
      foreach ($form['permissions']['#header'] as $key => &$data) {
        if (is_array($data) && array_key_exists('data', $data)) {
          $data['data'] = $role_name;
        }
      }
      $form['permissions'][$this->userRole->id()]['#default_value'] = [];
      $form['role_names']['#value'][$this->userRole->id()] = $role_name;
    }
    // Check for blocked permissions.
    $blocked_permissions = [];
    $user_roles = $this->user->getRoles();
    foreach (user_role_permissions($user_roles) as $rid => $permissions) {
      if ($rid != $role->get('id')) {
        $blocked_permissions += array_filter($permissions);
      }
    }
    $rid = $role->get('id');
    foreach ($blocked_permissions as $permission) {
      if (isset($form['permissions'][$permission][$rid])) {
        $form['permissions'][$permission][$rid]['#default_value'] = 1;
        $form['permissions'][$permission][$rid]['#value'] = $permission;
        $form['permissions'][$permission][$rid]['#disabled'] = TRUE;
        $form['permissions'][$permission][$rid]['#attributes']['checked'] = TRUE;
      }
    }

    $form['role_names'][$this->userRole->id()]['#markup'] = 'Enable?';
    $form['role_name'] = [
      '#type' => 'hidden',
      '#value' => $this->userRole->label(),
    ];
    $form['uid'] = [
      '#type' => 'hidden',
      '#value' => $uid,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $perms = [];

    $uid = (int) $form_state->getValue('uid');
    $role_name = $form_state->getValue('role_name');
    $input = $form_state->getUserInput();
    if (array_key_exists($role_name, $input)) {
      $perms = $input[$role_name];
    }

    if ($role_name == USER_PERMISSIONS_NO_ROLE) {
      if (!empty($perms)) {
        // Creates a new role with the name $role_name.
        $role_name = '_user_role_' . $uid;
        $this->userRole = Role::create([
          'id' => $role_name,
          'label' => $role_name,
        ]);
        $this->userRole->save();
        foreach ($form_state->getValue('role_names') as $role_name => $name) {
          user_role_change_permissions($this->userRole->label(), (array) $form_state->getValue($role_name));
        }
      }
      // If $perms contains no permissions for the user, no role is created.
    }
    else {
      // Modifying existing user permissions.
      $perms_exist = array_filter($perms);
      if (empty($perms_exist)) {
        // If $perms has no permissions for the user,
        // this deletes all permission/role information related to this role.
        $this->userRole->delete();
      }
      else {
        foreach ($form_state->getValue('role_names') as $role_name => $name) {
          user_role_change_permissions($role_name, (array) $form_state->getValue($role_name));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_permissions_form';
  }

}
