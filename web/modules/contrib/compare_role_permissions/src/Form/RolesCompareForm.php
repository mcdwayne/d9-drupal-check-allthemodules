<?php

namespace Drupal\compare_role_permissions\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\PermissionHandlerInterface;
use Drupal\user\RoleStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Url;

/**
 * Class RolesCompareForm.
 *
 * @package Drupal\compare_role_permissions\Form
 */
class RolesCompareForm extends FormBase {

  /**
   * @var RequestStack
   */
  private $request;

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
  public function __construct(PermissionHandlerInterface $permission_handler, RoleStorageInterface $role_storage, ModuleHandlerInterface $module_handler, RequestStack $request) {
    $this->permissionHandler = $permission_handler;
    $this->roleStorage = $role_storage;
    $this->moduleHandler = $module_handler;
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.permissions'), $container->get('entity.manager')->getStorage('user_role'), $container->get('module_handler'), $container->get('request_stack')
    );
  }

  /**
   * Gets the roles to display in this form.
   *
   * @return \Drupal\user\RoleInterface[]
   *   An array of role objects.
   */
  protected function getRolePermission($role_name = '') {
    return $this->roleStorage->load($role_name)->getPermissions();
  }

  /**
   * Gets the roles to display in this form.
   *
   * @return \Drupal\user\RoleInterface[]
   *   An array of role objects.
   */
  protected function getRoleLabel($role_name = '') {
    return $this->roleStorage->load($role_name)->label();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'roles_compare_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['select_role'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => $this->t('Select Roles to Compare'),
      '#options' => user_role_names(),
      '#required' => TRUE,
    ];
    $form['permission_type'] = [
      '#type' => 'select',
      '#required' => TRUE,
      '#title' => $this->t('Permission type'),
      '#options' => [
        'all_permissions' => 'All permissions',
        'matched_permissions' => 'Matched permissions',
        'different_permissions' => 'Different permissions'
      ],
      '#default_value' => 'different_permissions'
    ];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Compare'),
      '#button_type' => 'primary',
    ];
//    if ($form_state->isExecuted()) {
    $this->printRolePermissions($form, $form_state);
//    }
    return $form;
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   * @return array
   */
  public function printRolePermissions(array &$form, FormStateInterface $form_state) {
    if ($form_state->isExecuted()) {
      $permission_type = $form_state->getValue('permission_type');
      $doc_options['attributes'] = ['class' => 'permission-link'];
      $role = $form_state->getValue('select_role');
      $role_permissions = [];
      foreach ($role as $role_value) {
        $role_permissions[] = $this->getRolePermission($role_value);
      }

      $role_diff_merged = $this->getRoleDiffValue($role_permissions, $permission_type);
      $role_diff = $role_diff_merged['diff'];
      $role_match = $role_diff_merged['common'];

      $permissions = $this->permissionHandler->getPermissions();
      $listedPermissions = [];
      foreach ($permissions as $permission_name => $permission) {
        if ($permission_type == 'different_permissions' && in_array($permission_name, $role_diff)) {
          $listedPermissions[$permission_name] = $permission;
        }
        elseif ($permission_type == 'matched_permissions' && in_array($permission_name, $role_match)) {
          $listedPermissions[$permission_name] = $permission;
        }
        elseif ($permission_type == 'all_permissions') {
          $listedPermissions[$permission_name] = $permission;
        }
      }
      $form['permissions'] = [
        '#type' => 'table',
        '#header' => [
          'module' => 'Module',
          'permission' => 'Permission',
        ],
      ];

      foreach ($role as $role_value) {
        $form['permissions']['#header'][$role_value] = $this->getRoleLabel($role_value);
      }

      foreach ($listedPermissions as $perm => $perm_item) {
        $form['permissions'][$perm]['module'] = [
          '#markup' => $this->moduleHandler->getName($perm_item['provider'])
        ];
        $form['permissions'][$perm]['permission'] = [
          '#markup' => $perm_item['title']
        ];

        foreach ($role as $rid) {
          $form['permissions'][$perm][$rid] = [
            '#title' => ': ' . $perm_item['title'],
            '#title_display' => 'invisible',
            '#wrapper_attributes' => [
              'class' => ['checkbox'],
            ],
            '#type' => 'checkbox',
            '#default_value' => in_array($perm, $this->getRolePermission($rid)) ? 1 : 0,
            '#attributes' => ['class' => ['rid-' . $rid, 'js-rid-' . $rid]],
            '#parents' => [$rid, $perm],
          ];
        }
      }
      $form['actions']['sync_perms'] = [
        '#type' => 'submit',
        '#value' => $this->t('Update Permissions'),
        '#button_type' => 'primary',
      ];
      if (empty($listedPermissions)) {
        $form['no_diff'] = [
          '#markup' => 'No results found for the selected roles and the selected permission type',
        ];
      }
      return $form;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getRoleDiffValue($roles, $type) {
    $diff_merged = [];
    $intersected = $roles[0];
    $merged = [];
    for ($i = 0; $i < count($roles); $i++) {
      $merged = array_merge($merged, $roles[$i]);
      $intersected = array_intersect($intersected, $roles[$i]);
    }
    
    if ($type == 'different_permissions') {
      $diff_merged['diff'] = array_values(array_filter( array_unique(array_diff($merged, $intersected)) ));
    }
    elseif ($type == 'matched_permissions') {
      $diff_merged['common'] = array_values(array_filter(array_unique($intersected)));
    }
    else {
      $diff_merged['common'] = array_values(array_filter(array_unique($intersected)));
      $diff_merged['diff'] = array_values(array_filter( array_unique(array_diff($merged, $intersected)) ));
    }
    return $diff_merged;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Fetch the role.
    $role = $form_state->getValue('select_role');
    if (count($role) <= 1) {
      $form_state->setErrorByName('select_role', $this->t('Please select more than one role from list.'));
    }
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getTriggeringElement()['#value']->getUntranslatedString() == 'Update Permissions') {
      $roles = $form_state->getValue('select_role');
      foreach ($roles as $role_name) {
        user_role_change_permissions($role_name, (array) $form_state->getValue($role_name));
      }
      drupal_set_message($this->t('The changes have been saved.'));
    }
    else {
      $form_state->setRebuild();
    }
  }
}
