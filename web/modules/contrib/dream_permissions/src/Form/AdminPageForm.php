<?php

/**
 * @file
 * Contains \Drupal\dream_permissions\Form\AdminPageForm.
 */

namespace Drupal\dream_permissions\Form;

use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\PermissionHandlerInterface;
use Drupal\user\RoleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AdminPageForm extends ConfigFormBase {

  /**
   * @var \Drupal\user\PermissionHandlerInterface
   */
  protected $permissionHandler;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Creates a new AdminPageForm instance.
   *
   * @param \Drupal\user\PermissionHandlerInterface $permissionHandler
   *   The permission  handler.
   */
  public function __construct(PermissionHandlerInterface $permissionHandler, ModuleHandlerInterface $module_handler) {
    $this->permissionHandler = $permissionHandler;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('user.permissions'), $container->get('module_handler'));
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['dream_permissions.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dream_permissions_admin_page_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $roles = user_roles();
    asort($roles);
    $role_labels = array_map(function (RoleInterface $role) {
      return $role->label();
    }, $roles);

    $config = $this->config('dream_permissions.settings');
    $form['override_default_page'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Override default permissions page'),
      '#description' => $this->t('If checked, the default permissions page will be overridden with the dreams permissions page.'),
      '#default_value' => $config->get('override_default_page'),
    ];

    $form['excluded__roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select the roles you want to hide'),
      '#options' => $role_labels,
      '#default_value' => $config->get('excluded.roles'),
    ];

    // Only show modules defining a permission.
    $permissions = $this->permissionHandler->getPermissions();
    $modules_with_permissions = [];

    array_walk($permissions, function (array $permission) use (&$modules_with_permissions) {
      $modules_with_permissions[$permission['provider']] = TRUE;
    });

    $modules = array_intersect_key($this->moduleHandler->getModuleList(), $modules_with_permissions);
    asort($modules);
    $module_labels = array_map(function(Extension $module) {
      return $module->getName();
    }, $modules);

    $form['excluded__modules'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select the modules you want to hide'),
      '#options' => $module_labels,
      '#default_value' => $config->get('excluded.modules'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->config('dream_permissions.settings');
    $config->set('override_default_page', $form_state->getValue('override_default_page'));
    $config->set('excluded.roles', $form_state->getValue('excluded__roles'));
    $config->set('excluded.modules', $form_state->getValue('excluded__modules'));
  }


}
