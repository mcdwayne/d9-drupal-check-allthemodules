<?php

namespace Drupal\permissions_lock\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\user\PermissionHandlerInterface;
use Drupal\user\RoleStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PermissionsLockForm extends ConfigFormBase {

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
   * @param \Drupal\Core\Extension\ModuleHandlerInterface
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
   * Gets the roles to display in this form.
   *
   * @return \Drupal\user\RoleInterface[]
   *   An array of role objects.
   */
  protected function getRoles() {
    return $this->roleStorage->loadMultiple();
  }


  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'permissions_lock_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'permissions_lock.settings',
    ];
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = \Drupal::config('permissions_lock.settings');
    $default_roles = $config->get('permissions_lock_locked_roles');
    $default_perms = $config->get('permissions_lock_locked_perm');
    //$message = $config->get('permissions_lock_locked_roles');
    //echo '<pre>'; die(print_r());


    $permissions = array();
    foreach ($this->permissionHandler->getPermissions() as $key => $value) {
      // echo '<pre>'; die(print_r($key));
      $permissions[$key] = $key;
    }
    $form['permissions_lock_roles'] = array(
      '#type' => 'fieldset',
      '#title' => t('Roles'),
      '#weight' => 0,
      '#collapsible' => TRUE,
    );

    //$hook_lock_roles = permissions_lock_get_hook_data('role');
    $form['permissions_lock_roles']['permissions_lock_locked_roles'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Locked roles'),
      '#description' => t("Users without the 'manage permissions unrestricted' permission will not be able to change permissions for the selected roles."),
      "#default_value" => $default_roles,
      '#options' => user_role_names(),
      // '#options' => array(t('UK'), t('Other')),
    );

    //if ($hook_lock_roles) {

    //}

    $form['permissions_lock_permissions'] = array(
      '#type' => 'fieldset',
      '#title' => t('Permissions'),
      '#weight' => 0,
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    $form['permissions_lock_permissions']['permissions_lock_locked_perm'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Locked permissions'),
      '#description' => t("Specify which permissions will not be changeable by users without the 'manage permissions unrestricted' permission"),
      "#default_value" => $default_perms,
      '#options' => $permissions,
      // '#options' => array(t('UK'), t('Other')),
    );

    /* $hook_locks = permissions_lock_get_hook_data('permission');
    if ($hook_locks) {

    } */


    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
      '#weight' => 15,
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message($this->t('The configuration options have been saved.'));
    //echo '<pre>'; print_r($form_state->getValues()); die;
    //echo 'asda';
    $this->config('permissions_lock.settings')
      ->set('permissions_lock_locked_roles', $form_state->getValue('permissions_lock_locked_roles'))
      ->set('permissions_lock_locked_perm', $form_state->getValue('permissions_lock_locked_perm'))
      ->save();
    //$this->config('permissions_lock.settings')->delete();
  }

}