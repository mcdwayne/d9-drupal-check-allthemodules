<?php

namespace Drupal\pfm\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Form\UserPermissionsForm;

/**
 * Provides the user permissions administration PFM module form.
 */
class PfmUserPermissionsForm extends UserPermissionsForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pfm_user_admin_permissions';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $input = $form_state->getUserInput();
    $form['#prefix'] = '<div id="pfm-user-admin-permissions-wrapper">';
    $form['#suffix'] = '</div>';
    $form['permissions_filter'] = [
      '#type' => 'fieldset',
      '#title' => 'Permissions Filters',
      '#weight' => -10,
      '#attributes' => [
        'class' => ['form--inline', 'clearfix'],
      ],
    ];
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
    $roles_options = ['all' => 'All Roles'] + $role_names;
    $form['permissions_filter']['roles'] = [
      '#type' => 'select',
      '#options' => $roles_options,
      '#title' => $this->t('Select Role'),
      '#multiple' => TRUE,
      '#default_value' => 'all',
      '#weight' => -8,
      '#ajax' => [
        'callback' => [$this, 'ajaxRefresh'],
        'wrapper' => 'pfm-user-admin-permissions-wrapper',
        'progress' => ['type' => 'fullscreen'],
      ],
    ];

    // Store $role_names for use when saving the data.
    $form['role_names'] = [
      '#type' => 'value',
      '#value' => $role_names,
    ];
    // Render role/permission overview:
    $form['permissions'] = [
      '#type' => 'table',
      '#header' => [$this->t('Permission')],
      '#id' => 'permissions',
      '#attributes' => ['class' => ['permissions', 'js-permissions']],
      '#sticky' => TRUE,
      '#empty' => $this->t('Please choose at least one module.'),
    ];
    $selected_roles = isset($input['roles']) ? $input['roles'] : [];
    $selected_roles = array_intersect($selected_roles, array_keys($roles_options));
    foreach ($role_names as $role_id => $name) {
      if (in_array('all', $selected_roles) || in_array($role_id, $selected_roles)) {
        $form['permissions']['#header'][] = [
          'data' => $name,
          'class' => ['checkbox'],
        ];
      }
    }

    $permissions = $this->permissionHandler->getPermissions();
    $providers = $permissions_by_provider = [];
    foreach ($permissions as $permission_name => $permission) {
      $permissions_by_provider[$permission['provider']][$permission_name] = $permission;
      $providers[] = $permission['provider'];
    }
    $providers = array_unique($providers);
    $modules_list = array_keys($this->moduleHandler->getModuleList());
    $modules_list = array_intersect($modules_list, $providers);
    $modules_options = array_map(function ($module_name) {
      return $this->moduleHandler->getName($module_name);
    }, array_combine($modules_list, $modules_list));
    natcasesort($modules_options);
    $selected_modules = isset($input['modules']) ? $input['modules'] : [];
    $selected_modules = array_intersect($selected_modules, $modules_list);

    $form['permissions_filter']['modules'] = [
      '#type' => 'select',
      '#options' => $modules_options,
      '#title' => $this->t('Select Modules'),
      '#multiple' => TRUE,
      '#weight' => -9,
      '#ajax' => [
        'callback' => [$this, 'ajaxRefresh'],
        'wrapper' => 'pfm-user-admin-permissions-wrapper',
        'progress' => ['type' => 'fullscreen'],
      ],
    ];
    if (empty($selected_modules)) {
      return $form;
    }

    // Move the access content permission to the Node module if it is installed.
    if ($this->moduleHandler->moduleExists('node')) {
      // Insert 'access content' before the 'view own unpublished content' key
      // in order to maintain the UI even though the permission is provided by
      // the system module.
      $keys = array_keys($permissions_by_provider['node']);
      $offset = (int) array_search('view own unpublished content', $keys);
      $permissions_by_provider['node'] = array_merge(
        array_slice($permissions_by_provider['node'], 0, $offset),
        ['access content' => $permissions_by_provider['system']['access content']],
        array_slice($permissions_by_provider['node'], $offset)
      );
      unset($permissions_by_provider['system']['access content']);
    }
    $table_build_info = [
      $permissions_by_provider,
      $role_names,
      $role_permissions,
      $admin_roles,
      $selected_modules,
      $selected_roles,
    ];
    // Add method for possible overrides if needed.
    $this->fillPermissionTable($form, $form_state, $table_build_info);

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save permissions'),
      '#button_type' => 'primary',
    ];
    $form['#attached']['library'][] = 'user/drupal.user.permissions';
    // Add extra functionality if permissions_dragcheck module exist.
    if ($this->moduleHandler->moduleExists('permissions_dragcheck')) {
      // Add the library:
      $form['#attached']['library'][] = 'permissions_dragcheck/drag-check-js';
      // Init:
      $form['#attached']['library'][] = 'permissions_dragcheck/permissions-drag-check';
    }

    return $form;
  }

  /**
   * Helper method for filling Permission Table.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $table_build_info
   *   The current table build information.
   */
  protected function fillPermissionTable(array &$form, FormStateInterface &$form_state, array $table_build_info) {
    list(
      $permissions_by_provider,
      $role_names,
      $role_permissions,
      $admin_roles,
      $selected_modules,
      $selected_roles,
      ) = $table_build_info;

    foreach ($permissions_by_provider as $provider => $permissions) {
      // Module name.
      if (!in_array($provider, $selected_modules)) {
        continue;
      }
      $form['permissions'][$provider] = [
        [
          '#wrapper_attributes' => [
            'colspan' => count($role_names) + 1,
            'class' => ['module'],
            'id' => 'module-' . $provider,
          ],
          '#markup' => $this->moduleHandler->getName($provider),
        ],
      ];
      foreach ($permissions as $perm => $perm_item) {
        // Fill in default values for the permission.
        $perm_item += [
          'description' => '',
          'restrict access' => FALSE,
          'warning' => !empty($perm_item['restrict access']) ? $this->t('Warning: Give to trusted roles only; this permission has security implications.') : '',
        ];
        $form['permissions'][$perm]['description'] = [
          '#type' => 'inline_template',
          '#template' => '<div class="permission"><span class="title">{{ title }}</span>{% if description or warning %}<div class="description">{% if warning %}<em class="permission-warning">{{ warning }}</em> {% endif %}{{ description }}</div>{% endif %}</div>',
          '#context' => [
            'title' => $perm_item['title'],
          ],
        ];
        $form['permissions'][$perm]['description']['#context']['description'] = $perm_item['description'];
        $form['permissions'][$perm]['description']['#context']['warning'] = $perm_item['warning'];
        foreach ($role_names as $rid => $name) {
          if (in_array('all', $selected_roles) || in_array($rid, $selected_roles)) {
            $checked = in_array($perm, $role_permissions[$rid]) ? TRUE : FALSE;
            $form['permissions'][$perm][$rid] = [
              '#title' => $name . ': ' . $perm_item['title'],
              '#title_display' => 'invisible',
              '#wrapper_attributes' => [
                'class' => ['checkbox'],
              ],
              '#type' => 'checkbox',
              '#default_value' => $checked,
              '#attributes' => [
                'class' => ['rid-' . $rid, 'js-rid-' . $rid],
                'checked' => $checked,
              ],
              '#parents' => [$rid, $perm],
            ];
            // Show a column of disabled but checked checkboxes.
            if ($admin_roles[$rid]) {
              $form['permissions'][$perm][$rid]['#disabled'] = TRUE;
              $form['permissions'][$perm][$rid]['#default_value'] = TRUE;
            }
          }
        }
      }
    }
  }

  /**
   * AJAX refresh callback for the form.
   */
  public function ajaxRefresh(&$form, FormStateInterface $form_state) {
    return $form;
  }

}
