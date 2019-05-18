<?php

/**
 * @file
 * Contains \Drupal\hawk_auth\Form\HawkPermissionsForm.
 */

namespace Drupal\hawk_auth\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hawk_auth\Entity\HawkCredentialStorageInterface;
use Drupal\user\PermissionHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for setting hawk credential's revoke permissions.
 */
class HawkPermissionsForm extends ContentEntityForm {

  /**
   * Hawk credential entity's storage.
   *
   * @var \Drupal\hawk_auth\Entity\HawkCredentialStorageInterface
   */
  protected $hawkCredentialStorage;

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
   * Constructs Hawk controller object.
   *
   * @param HawkCredentialStorageInterface $hawk_credential_storage
   *   Storage for Hawk Credentials' entities.
   * @param \Drupal\user\PermissionHandlerInterface $permission_handler
   *   The permission handler.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler.
   */
  public function __construct(HawkCredentialStorageInterface $hawk_credential_storage, PermissionHandlerInterface $permission_handler, ModuleHandlerInterface $module_handler) {
    $this->hawkCredentialStorage = $hawk_credential_storage;
    $this->permissionHandler = $permission_handler;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Entity\EntityManagerInterface $entity_manager */
    $entity_manager = $container->get('entity.manager');

    return new static(
      $entity_manager->getStorage('hawk_credential'),
      $container->get('user.permissions'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hawk_credential_permissions';
  }

  /**
   * Copied a fair amount from Drupal\user\Form\UserPermissionForm::buildForm.
   *
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\hawk_auth\Entity\HawkCredentialInterface $credential */
    $credential = $this->getEntity();

    // Render role/permission overview:
    $options = [];
    $hide_descriptions = system_admin_compact_mode();

    $form['#title'] = $this->t('Editing revoke permissions for Hawk Credential ID #%id', [
      '%id' => $credential->id(),
    ]);

    // User's with ID 1 cannot revoke permissions due to the way Drupal
    // handles permission checks.
    if ($credential->getOwner()->id() == 1) {
      return $form;
    }

    $form['permissions'] = array(
      '#type' => 'table',
      '#header' => [$this->t('Permission'), $this->t('Revoke')],
      '#id' => 'permissions',
      '#attributes' => ['class' => ['permissions', 'js-permissions']],
      '#sticky' => TRUE,
    );

    $permissions = $this->permissionHandler->getPermissions();
    $permissions_by_provider = array();
    foreach ($permissions as $permission_name => $permission) {
      $permissions_by_provider[$permission['provider']][$permission_name] = $permission;
    }

    foreach ($permissions_by_provider as $provider => $permissions) {
      // Module name.
      $form['permissions'][$provider] = array(array(
        '#wrapper_attributes' => array(
          'colspan' => 3,
          'class' => array('module'),
          'id' => 'module-' . $provider,
        ),
        '#markup' => $this->moduleHandler->getName($provider),
      ));

      foreach ($permissions as $perm => $perm_item) {
        if (!$credential->getOwner()->hasPermission($perm)) {
          continue;
        }

        // Fill in default values for the permission.
        $perm_item += array(
          'description' => '',
          'restrict access' => FALSE,
          'warning' => !empty($perm_item['restrict access']) ? $this->t('Warning: Give to trusted roles only; this permission has security implications.') : '',
        );
        $options[$perm] = $perm_item['title'];
        $form['permissions'][$perm]['description'] = array(
          '#type' => 'inline_template',
          '#template' => '<div class="permission"><span class="title">{{ title }}</span>{% if description or warning %}<div class="description">{% if warning %}<em class="permission-warning">{{ warning }}</em> {% endif %}{{ description }}</div>{% endif %}</div>',
          '#context' => array(
            'title' => $perm_item['title'],
          ),
        );
        // Show the permission description.
        if (!$hide_descriptions) {
          $form['permissions'][$perm]['description']['#context']['description'] = $perm_item['description'];
          $form['permissions'][$perm]['description']['#context']['warning'] = $perm_item['warning'];
        }
        $options[$perm] = '';
        $form['permissions'][$perm][] = array(
          '#title' => $perm_item['title'],
          '#title_display' => 'invisible',
          '#wrapper_attributes' => array(
            'class' => array('checkbox'),
          ),
          '#type' => 'checkbox',
          '#default_value' => $credential->revokesPermission($perm) ? 1 : 0,
        );
      }
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save permissions'),
      '#button_type' => 'primary',
    );

    $form['#attached']['library'][] = 'user/drupal.user.permissions';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\hawk_auth\Entity\HawkCredentialInterface $credential */
    $credential = $this->getEntity();

    $permissions_list = $form_state->getValue('permissions');
    $revoke_permissions = array();
    foreach ($permissions_list as $perm => $status) {
      if (!empty($status[0]) && $credential->getOwner()->hasPermission($perm)) {
        $revoke_permissions[] = $perm;
      }
    }

    $credential->setRevokePermissions($revoke_permissions);
    $credential->save();

    $form_state->setRedirect('hawk_auth.user_credential', ['user' => $credential->getOwner()->id()]);
  }

}
