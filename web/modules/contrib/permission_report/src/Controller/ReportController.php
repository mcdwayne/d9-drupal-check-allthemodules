<?php
/**
 * @file
 * Report controller.
 */

namespace Drupal\permission_report\Controller;

use \Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\user\RoleStorageInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\user\PermissionHandlerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\user\RoleInterface;
use Drupal\Core\Render\Markup;

/**
 * Report controller class for displaying reports.
 *
 * @package Drupal\permission_report\Controller
 */
class ReportController extends ControllerBase {
  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;
  /**
   * Permission handler interface.
   *
   * @var \Drupal\user\PermissionHandlerInterface
   */
  protected $permissionHandler;
  /**
   * The module handler interface.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;
  /**
   * Role storage manager.
   *
   * @var \Drupal\user\RoleStorageInterface
   */
  protected $roleStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('user.permissions'),
      $container->get('module_handler'),
      $container->get('entity.manager')->getStorage('user_role')
    );
  }

  /**
   * Constructs a ReportController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   A database connection.
   */
  public function __construct(Connection $database, PermissionHandlerInterface $permission_handler, ModuleHandlerInterface $module_handler, RoleStorageInterface $role_storage) {
    $this->database = $database;
    $this->permissionHandler = $permission_handler;
    $this->moduleHandler = $module_handler;
    $this->roleStorage = $role_storage;
  }

  /**
   * Returns a permission list and the number of users who have that permission.
   *
   * @return array
   *   Build array with the report.
   */
  public function permissionList() {
    // Render role/permission overview:
    $rows = [];
    $can_admin_access = \Drupal::currentUser()->hasPermission('administer access control');

    $permissions = $this->permissionHandler->getPermissions();

    $permissions_by_provider = [];
    foreach ($permissions as $permission_name => $permission) {
      $permissions_by_provider[$permission['provider']][$permission_name] = $permission;
    }

    $users_in_roles = $this->database->select('user__roles', 'ur');
    $users_in_roles->fields('ur', ['roles_target_id']);
    $users_in_roles->innerJoin('users', 'u', 'u.uid = ur.entity_id');
    $users_in_roles->innerJoin('users_field_data', 'ufd', 'ufd.uid = u.uid');
    $users_in_roles->addExpression('COUNT(roles_target_id)', 'user_count');
    $users_in_roles->where('ufd.status = :ufd_status', [':ufd_status' => 1]);
    $users_in_roles->groupBy('ur.roles_target_id');
    $users_in_roles->orderBy('ur.roles_target_id');
    $result = $users_in_roles->execute();

    foreach ($result as $role_info) {
      $roles_included[$role_info->roles_target_id] = $role_info->user_count;
    }

    foreach ($permissions_by_provider as $provider => $module_permissions) {
      $rows[] = [
        [
          'data' => $this->t('@module module', ['@module' => $this->moduleHandler->getName($provider)]),
          'class' => 'module' ,
          'id' => 'module-' . $provider,
          'colspan' => 3,
        ],
      ];

      asort($module_permissions);

      foreach ($module_permissions as $perm => $meta) {
        $users_having_role = 0;
        $options = [];
        $display_roles = [];
        $roles = $this->rolesHavingPermission($perm);

        if (array_key_exists('description', $meta)) {
          $options = ['attributes' => ['alt' => $meta['description']]];
        }

        foreach ($roles as $role) {
          /* @var $role RoleInterface */
          $display_roles[] = $can_admin_access ? $this->l($role->label(), new Url('permission_report.role', ['user_role' => $role->id()])) : $role->label();
          $users_having_role += $roles_included[$role->id()];
        }

        $rows[] = [
          [
            'data' => $this->l($meta['title'],
              new Url('permission_report.permission',
                ['user_permission' => $perm], $options)),
          ],
          ['data' => $users_having_role],
          ['data' => Markup::create(implode(', ', $display_roles))],
        ];
      }
    }

    $build['user_permission_report'] = [
      '#type' => 'table',
      '#rows' => $rows,
      '#header' => ['Permission', 'Users', 'Roles'],
    ];

    return $build;
  }

  /**
   * Output a role list and the number of users that have that role.
   *
   * @return array
   *   Formatted role report list.
   */
  public function roleList() {
    $roles = \Drupal::entityManager()->getStorage('user_role')->loadMultiple();
    $rows = [];

    $users_in_roles = $this->database->select('user__roles', 'ur');
    $users_in_roles->fields('ur', ['roles_target_id']);
    $users_in_roles->innerJoin('users', 'u', 'u.uid = ur.entity_id');
    $users_in_roles->innerJoin('users_field_data', 'ufd', 'ufd.uid = u.uid');
    $users_in_roles->addExpression('COUNT(roles_target_id)', 'user_count');
    $users_in_roles->where('ufd.status = :ufd_status', [':ufd_status' => 1]);
    $users_in_roles->groupBy('ur.roles_target_id');
    $users_in_roles->orderBy('ur.roles_target_id');

    $can_admin_access = \Drupal::currentUser()->hasPermission('administer access control');
    $result = $users_in_roles->execute();

    // Build our table.
    foreach ($result as $role_info) {
      $label = $roles[$role_info->roles_target_id]->label();
      $rows[] = [
        $can_admin_access ? $this->l($label, new Url('entity.user_role.edit_permissions_form', ['user_role' => $role_info->roles_target_id])) : $label,
        $this->l($this->getStringTranslation()->formatPlural($role_info->user_count, '1 user', '@count users'), new Url('permission_report.role', ['user_role' => $role_info->roles_target_id])),
      ];
    }

    $header = [
      [
        'data' => $this->t('Role'),
        'field' => 'name',
        'sort' => 'desc',
        'colspan' => 2,
      ],
    ];

    $build['permission_report_overview']  = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];

    return $build;
  }

  /**
   * For a given role, list the users who have it.
   *
   * @param string $user_role
   *   The string id of the user role to generate a report for.
   *
   * @return array
   *   Build array.
   */
  public function roleDetail($user_role) {
    $rows = [];

    $role_name = \Drupal::entityManager()->getStorage('user_role')->load($user_role)->label();

    $query = $this->database->select('users', 'u');
    $query->fields('ufd', ['uid', 'name']);
    $query->innerJoin('user__roles', 'ur', 'ur.entity_id = u.uid');
    $query->innerJoin('users_field_data', 'ufd', 'ufd.uid = u.uid');
    $query->condition('ur.roles_target_id', $user_role);
    $query->condition('ufd.status', 1);

    $users_having_role = $query->execute();

    $view_users = \Drupal::currentUser()->hasPermission('access user profiles');

    $users_header = [
      'data' => 'User',
      'colspan' => 2,
    ];

    foreach ($users_having_role as $user) {
      $user_link = ($user->uid !== 0) ? ($view_users ? $this->l($user->name, new Url('entity.user.canonical', ['user' => $user->uid])) : $user->name) : \Drupal::config('user.settings')->get('anonymous');
      $user_report_link = $this->l($this->t('Permission report'), new Url('permission_report.user', ['user' => $user->uid]));
      $rows[] = [
        ['data' => $user_link],
        ['data' => $user_report_link],
      ];
    }

    $build['permission_report_role_detail'] = [
      '#type' => 'table',
      '#header' => [ $users_header ],
      '#rows' => $rows,
    ];

    $build['#title'] = $this->t('Users in "!name" role', ['!name' => $role_name]);

    return $build;
  }

  /**
   * Generate a report for a given user permission.
   *
   * @param string $user_permission
   *   The permission to build a report for.
   *
   * @return array
   *   Build array.
   */
  public function permissionDetail($user_permission) {
    // Render role/permission overview:
    $role_rows = $user_rows = [];
    $roles_containing_permission = [];
    $can_admin_access = \Drupal::currentUser()->hasPermission('administer access control');

    $roles = \Drupal::entityManager()->getStorage('user_role')->loadMultiple();

    /* @var \Drupal\user\Entity\Role $role */
    foreach ($roles as $id => $role) {
      if (!$role->hasPermission($user_permission)) {
        continue;
      }
      $roles_containing_permission[] = $id;
      $role_edit_link = $can_admin_access ? $this->l($role->label(), new Url('entity.user_role.edit_permissions_form', ['user_role' => $id])) : $role->label();
      $role_link = $can_admin_access ? $this->l('Permission report', new Url('permission_report.role', ['user_role' => $id])) : '';
      $role_rows[] = [
        ['data' => $role_edit_link],
        ['data' => $role_link],
      ];
    }

    $query = $this->database->select('users', 'u');
    $query->fields('ufd', ['uid', 'name']);
    $query->innerJoin('user__roles', 'ur', 'ur.entity_id = u.uid');
    $query->innerJoin('users_field_data', 'ufd', 'ufd.uid = u.uid');
    $query->condition('ur.roles_target_id', $roles_containing_permission, 'IN');
    $query->condition('ufd.status', 1);

    $users_having_role = $query->execute();

    $view_users = \Drupal::currentUser()->hasPermission('access user profiles');

    foreach ($users_having_role as $user) {
      $user_column = ($user->uid !== 0) ? ($view_users ? $this->l($user->name, new Url('entity.user.canonical', ['user' => $user->uid])) : $user->name) : \Drupal::config('user.settings')->get('anonymous');
      $permission_report_link = $this->l($this->t('Permission report'), new Url('permission_report.user', ['user' => $user->uid]));

      $user_rows[] = [
        ['data' => $user_column],
        ['data' => $permission_report_link],
      ];
    }

    $build['users_having_permission'] = [
      '#prefix' => '<h2>' . $this->t('Users') . '</h2>',
      '#type' => 'table',
      '#rows' => $user_rows,
      '#header' => ['User', 'Report'],
    ];

    $build['roles_having_permission'] = [
      '#prefix' => '<h2>' . $this->t('Roles') . '</h2>',
      '#type' => 'table',
      '#rows' => $role_rows,
      '#header' => ['Role', 'Report'],
    ];

    $build['#title'] = $this->t('Permissions report for "!perm"', ['!perm' => $user_permission]);

    return $build;
  }

  /**
   * Build a permission report for a given user.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user to build a report for.
   *
   * @return array
   *   Build array.
   */
  public function userDetail(UserInterface $user) {
    // Render role/permission overview:
    $rows = [];
    $can_admin_access = \Drupal::currentUser()->hasPermission('administer access control');

    $permissions = $this->permissionHandler->getPermissions();

    $permissions_by_provider = [];
    foreach ($permissions as $permission_name => $permission) {
      $permissions_by_provider[$permission['provider']][$permission_name] = $permission;
    }

    foreach ($permissions_by_provider as $provider => $module_permissions) {
      $rows[] = [
        [
          'data' => $this->t('@module module', ['@module' => $this->moduleHandler->getName($provider)]),
          'class' => 'module',
          'id' => 'module-' . $provider,
          'colspan' => 3,
        ],
      ];

      asort($module_permissions);

      foreach ($module_permissions as $perm => $meta) {
        $options = [];
        $display_roles = [];
        $roles = $this->rolesHavingPermission($perm);

        if (array_key_exists('description', $meta)) {
          $options = ['attributes' => ['alt' => $meta['description']]];
        }

        foreach ($roles as $role) {
          /* @var $role RoleInterface */
          $display_roles[] = $can_admin_access ? $this->l($role->label(), new Url('permission_report.role', ['user_role' => $role->id()])) : $role->label();
        }

        $rows[] = [
          [
            'data' => $this->l($meta['title'],
              new Url('permission_report.permission',
              ['user_permission' => $perm], $options)),
          ],
          ['data' => $user->hasPermission($perm) ? 'Yes' : 'No'],
          ['data' => Markup::create(implode(', ', $display_roles))],
        ];
      }
    }

    $build['user_permission_report'] = [
      '#type' => 'table',
      '#rows' => $rows,
      '#header' => ['Permission', 'Access', 'Roles'],
    ];

    return $build;
  }


  /**
   * Return a list of the roles that have a particular permission.
   *
   * @param string $permission
   *   The permission for which to load roles.
   *
   * @return RoleInterface[]
   *   An array of the roles that have a particular permission.
   */
  protected function rolesHavingPermission($permission) {
    $roles = $this->roleStorage->loadMultiple();
    foreach ($roles as $role) {
      /* @var $role RoleInterface */
      if ($role->hasPermission($permission)) {
        $matching_roles[] = $role;
      }
    }
    return $matching_roles;
  }

}
