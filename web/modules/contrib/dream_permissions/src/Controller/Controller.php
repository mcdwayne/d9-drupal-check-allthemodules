<?php

/**
 * @file
 * Contains \Drupal\dream_permissions\Controller\Controller.
 */

namespace Drupal\dream_permissions\Controller;

use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Controller\ControllerBase;
use Drupal\user\Entity\Role;
use Drupal\user\PermissionHandlerInterface;
use Drupal\user\RoleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class Controller extends ControllerBase {

  /**
   * @var \Drupal\user\PermissionHandlerInterface
   */
  protected $permissionHandler;

  /**
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $tokenGenerator;

  /**
   * Creates a new Controller instance.
   *
   * @param \Drupal\user\PermissionHandlerInterface $permission_handler
   *   The permission handler.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $token_generator
   *   The token generator.
   */
  public function __construct(PermissionHandlerInterface $permission_handler, CsrfTokenGenerator $token_generator) {
    $this->permissionHandler = $permission_handler;
    $this->tokenGenerator = $token_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.permissions'),
      $container->get('csrf_token')
    );
  }

  /**
   * Fetch permissions for certain modules and roles.
   */
  public function fetch($modules, $roles, $permission_filter) {
    // @TODO split in 2 parts: logic and json.
    $module_info = $this->moduleHandler()->getModuleList();
    $modules = explode(',', $modules);
    $roles = explode(',', $roles);

    $role_names = user_roles();
    $selected_roles = array();

    // Always add authenticed role.
    $selected_roles[RoleInterface::AUTHENTICATED_ID] = Role::load(RoleInterface::AUTHENTICATED_ID);

    foreach ($roles as $role) {
      $selected_roles[$role] = $role_names[$role];
    }
    $role_permissions = user_role_permissions(array_keys($selected_roles));

    $selected_permissions = array();
    $permissions_names = array();

    $permissions = $this->permissionHandler->getPermissions();

    foreach ($modules as $module) {
      $permissions_by_module = array_filter($permissions, function (array $permission) use ($module) {
        return $permission['provider'] == $module;
      });
      foreach ($permissions_by_module as $perm => $perm_item) {
        if ($permission_filter) {
          if (stripos($perm, $permission_filter) === FALSE && stripos($module_info[$module]['name'], $permission_filter) === FALSE && stripos($module_info[$module]['project'], $permission_filter) === FALSE && stripos($module_info[$module]['project'], $permission_filter) === FALSE) {
            continue;
          }
        }
        $permissions_names[$perm] = $perm_item['title'] . ' [' . $module_info[$module]->getName() . ']';
        foreach ($selected_roles as $rid => $name) {
          $selected_permissions[$rid][$perm] = (string) intval(isset($role_permissions[$rid][$perm]) ? $role_permissions[$rid][$perm] : FALSE);
        }
      }
    }
    ksort($selected_permissions);

    return new JsonResponse(array(
      'modules' => $modules,
      'roles' => array_map(function (RoleInterface $role) { return $role->label(); }, $selected_roles),
      'permissions' => $selected_permissions,
      'permissionsNames' => $permissions_names,
      'token' => $this->tokenGenerator->get(serialize($selected_permissions)),
    ));
  }

  public function save(Request $request) {
    $token = $request->request->get('token');
    $original_permissions = $request->request->get('originalPermissions');
    ksort($original_permissions);
    $permissions = $request->request->get('permissions');

    if (!$this->tokenGenerator->validate($token, serialize($original_permissions))) {
      throw new AccessDeniedHttpException();
    }

    // Only save roles that were requested.
    $keys_role_original = array_keys($original_permissions);
    $keys_role_new = array_keys($permissions);
    $keys_invalid = array_diff($keys_role_new, $keys_role_original);
    if (!empty($keys_invalid)) {
      $permissions = array_diff_key($permissions, array_flip($keys_invalid));
      throw new AccessDeniedHttpException();
    }

    // Only save permissions that were requested.
    $keys_perm_original = array_flip(array_keys($original_permissions[2]));

    foreach ($permissions as $rid => $perms) {
      $diff = array_diff_key($keys_perm_original, $perms);
      if (empty($diff)) {
        user_role_change_permissions($rid, $perms);
      }
      else {
        throw new AccessDeniedHttpException();
      }
    }

    return new Response(200);
  }

}
