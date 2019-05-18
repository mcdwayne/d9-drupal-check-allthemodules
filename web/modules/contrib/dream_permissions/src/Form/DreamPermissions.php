<?php

/**
 * @file
 * Contains \Drupal\dream_permissions\Form\DreamPermissions.
 */

namespace Drupal\dream_permissions\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\user\PermissionHandlerInterface;
use Drupal\user\RoleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DreamPermissions extends FormBase {

  /**
   * The permission handler.
   *
   * @var \Drupal\user\PermissionHandlerInterface
   */
  protected $permissionsHandler;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Creates a new DreamPermissions instance.
   *
   * @param \Drupal\user\PermissionHandlerInterface $permissionsHandler
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   */
  public function __construct(PermissionHandlerInterface $permissionsHandler, ModuleHandlerInterface $moduleHandler) {
    $this->permissionsHandler = $permissionsHandler;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.permissions'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dream_permissions';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('dream_permissions.settings');

    $current_step = 0;
    if ($form_state->getValues()) {
      $current_step = 1;
    }
    $form_state->set('step', $current_step);

    $permissions = $this->permissionsHandler->getPermissions();
    $modules_with_permissions = array_unique(array_values(array_map(function (array $permission) {
      return $permission['provider'];
    }, $permissions)));

    if ($current_step == 0) {
      // Retrieve role names.
      $roles = user_roles();
      $roles = array_diff_key($roles, array_filter($config->get('excluded.roles')));
      $role_labels = array_map(function (RoleInterface $role) {
        return $role->label();
      }, $roles);


      // Fetch permissions for authenticated roles.
      $auth_permissions = user_role_permissions([RoleInterface::AUTHENTICATED_ID]);

      // Only show modules defining a permission.
      $module_implements = array_diff($modules_with_permissions, array_filter($config->get('excluded.modules')));

      $modules = array();
      foreach ($module_implements as $module) {
        $modules[$module] = $this->moduleHandler->getModuleList()[$module]->getName();
      }
      asort($modules);

      $form['roles'] = array(
        '#type' => 'fieldset',
        '#title' => $this->t('Filter by roles'),
        '#collapsible' => TRUE,
        '#collapsed' => FALSE,
      );
      $form['roles']['filter'] = array(
        '#type' => 'textfield',
        '#attributes' => array(
          'class' => array('dream-permissions--filter', 'no-js'),
          'placeholder' => $this->t('Filter ...'),
        ),
      );
      $form['roles']['rids'] = array(
        '#type' => 'checkboxes',
        '#options' => $role_labels,
        '#attributes' => array('class' => array('dream-permissions--role-names')),
      );

      $form['modules'] = array(
        '#type' => 'fieldset',
        '#title' => $this->t('Filter by modules'),
        '#collapsible' => TRUE,
        '#collapsed' => FALSE,
      );
      $form['modules']['filter'] = array(
        '#type' => 'textfield',
        '#attributes' => array(
          'class' => array('dream-permissions--filter', 'no-js'),
          'placeholder' => $this->t('Filter ...'),
        ),
      );
      $form['modules']['mods'] = array(
        '#type' => 'checkboxes',
        '#options' => $modules,
        '#attributes' => array('class' => array('dream-permissions--module-names')),
      );

      $form['permissions'] = array(
        '#type' => 'fieldset',
        '#title' => $this->t('Filter by permission'),
        '#collapsible' => TRUE,
        '#collapsed' => FALSE,
      );
      $form['permissions']['filter'] = array(
        '#type' => 'textfield',
        '#attributes' => array(
          'class' => array('dream-permissions--filter-permission'),
          'placeholder' => $this->t('Filter ...'),
        ),
      );

      $form['checks'] = array(
        '#type' => 'container',
        '#attributes' => array(
          'class' => array('dream-permissions--checks'),
        ),
      );

      $form['#attributes'] = array('class' => array('dream-permissions--form'));
      $form['#attached']['library'][] = 'dream_permissions/dream-permissions';
      $form['#attached']['drupalSettings'][] = array(
        'dreamPermissions' => array(
          'roles' => $role_labels,
          'auth_permissions' => $auth_permissions,
          'modules' => $modules,
        ),
      );

      $form['actions'] = array('#type' => 'actions');
      $form['actions']['submit'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Fetch permissions'),
      );
    }
    else {
      // Fetch permissions.
      $modules = array_filter($form_state->getValue('mods'));
      $roles = array_filter($form_state->getValue('rids'));
      $permission_filter = $form_state->getValue('filter');

      // Retrieve role names for columns.

      // Always add authenticed role.
      $selected_roles = [];
      $selected_roles[RoleInterface::AUTHENTICATED_ID] = RoleInterface::AUTHENTICATED_ID;

      foreach ($roles as $rid) {
        $selected_roles[$rid] = $rid;
      }
      $role_permissions = user_role_permissions($selected_roles);

      $form['checkboxes'] = array(
        '#type' => 'table',
        '#tree' => TRUE,
      );

      $header = [];
      $header[] = $this->t('Permission');
      foreach ($selected_roles as $rid => $role_name) {
        $header[] = $role_name;
      }
      $form['checkboxes']['#header'] = $header;

      $permissions_by_modules = [];
      array_walk($permissions, function (array $permission, $name) use (&$permissions_by_modules) {
        $permissions_by_modules[$permission['provider']][$name] = $permission;
      });
      $permissions_by_modules = array_intersect_key($permissions_by_modules, array_flip($modules));

      foreach ($modules as $module) {
        if (!empty($permissions_by_modules[$module])) {
          foreach ($permissions as $perm => $perm_item) {
            if ($permission_filter) {
              // @todo add back support for project filtering.
              if (stripos($perm, $permission_filter) === FALSE && stripos($this->moduleHandler->getModuleList()[$module]->getName(), $permission_filter)) {
                continue;
              }
            }
            $form['checkboxes'][$perm]['label'] = array(
              '#markup' => $perm_item['title'],
            );
            foreach ($selected_roles as $rid => $name) {
              $form['checkboxes'][$perm][$rid] = array(
                '#type' => 'checkbox',
                '#title' => $perm_item['title'],
                '#title_display' => 'invisible',
                '#default_value' => isset($role_permissions[$rid][$perm]) ? $role_permissions[$rid][$perm] : FALSE,
              );
            }
          }
        }
      }

      // Rows.
      $rows = array();
      foreach (Element::children($form['checkboxes']) as $key) {
        $row = array();
        foreach (Element::children($form['checkboxes'][$key]) as $key2) {
          $row[]['data'] =& $form['checkboxes'][$key][$key2];
        }
        $rows[] = array(
          'data' => $row,
        );
      }

      $form['actions'] = array('#type' => 'actions');
      $form['actions']['submit'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Save permissions'),
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->set(['storedvalues', $form_state->get('step')], $form_state->getValues());
    if ($form_state->get('step') == 0) {
      // Rebuild form to display permissions.
      $form_state->setRebuild();
    }
    else {
      $checkboxes = $form_state->getValue('checkboxes');
      $permissions = array();
      foreach ($checkboxes as $perm => $roles) {
        foreach ($roles as $rid => $value) {
          $permissions[$rid][$perm] = $value;
        }
      }
      foreach ($permissions as $rid => $values) {
        user_role_change_permissions($rid, $values);
      }
    }
  }


}
