<?php

namespace Drupal\views_field_permissions;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\PermissionHandlerInterface;

/**
 * The views field permissions service.
 */
class ViewsUiFormService implements ViewsUiFormServiceInterface {

  /**
   * The permission handler.
   *
   * @var \Drupal\user\PermissionHandlerInterface
   */
  protected $permissionHandler;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a object.
   *
   * @param \Drupal\user\PermissionHandlerInterface $permission_handler
   *   The permission handler.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(PermissionHandlerInterface $permission_handler, ModuleHandlerInterface $module_handler) {
    $this->permissionHandler = $permission_handler;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array &$form, FormStateInterface &$form_state) {
    $options = $form_state->getStorage()['handler']->options;

    // Get list of permissions
    $perms = ['' => t('- None -')];
    $permissions = $this->permissionHandler->getPermissions();
    foreach ($permissions as $perm => $perm_item) {
      $provider = $perm_item['provider'];
      $display_name = $this->moduleHandler->getName($provider);
      $perms[$display_name][$perm] = strip_tags($perm_item['title']);
    }


    $form['options']['expose']['views_field_permissions'] = [
      '#title' => t('Views Field Permissions'),
      '#type' => 'details',
    ];

    $form['options']['expose']['views_field_permissions']['perm'] = [
      '#type' => 'select',
      '#options' => $perms,
      '#title' => t('Permission'),
      '#parents' => ['options', 'views_field_permissions', 'perm'],
      '#default_value' => isset($options['views_field_permissions']['perm']) ? $options['views_field_permissions']['perm'] : NULL,
    ];

    $form['actions']['submit']['#submit'][] = [get_class($this), 'submit'];
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function submit(array &$form, FormStateInterface &$form_state) {
    $view = $form_state->get('view');
    $display_id = $form_state->get('display_id');
    $id = $form_state->get('id');
    $type = $form_state->get('type');
    $executable = $view->getExecutable();
    $handler = $executable->getHandler($display_id, $type, $id);

    // Set values.
    $state_options = $form_state->getValue('options', []);
    if (!empty($state_options['views_field_permissions']['perm'])) {
      $handler['views_field_permissions'] = $state_options['views_field_permissions'];
    }
    else {
      unset($handler['views_field_permissions']);
    }
    $executable->setHandler($display_id, $type, $id, $handler);

    // Write to cache
    $view->cacheSet();
  }

}
