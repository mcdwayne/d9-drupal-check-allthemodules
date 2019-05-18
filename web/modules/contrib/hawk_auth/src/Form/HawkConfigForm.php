<?php

/**
 * @file
 * Contains Drupal\hawk_auth\Form\HawkConfigForm.
 */

namespace Drupal\hawk_auth\Form;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\RoleInterface;
use Drupal\user\RoleStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for showing configuration settings related to hawk.
 */
class HawkConfigForm extends ConfigFormBase {

  /**
   * Role storage.
   *
   * @var RoleStorageInterface
   */
  protected $roleStorage;

  /**
   * Constructs an object of HawkConfigForm.
   *
   * @param ConfigFactoryInterface $config_factory
   *   Factory for config objects.
   * @param RoleStorageInterface $role_storage
   *   Role storage for managing roles.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RoleStorageInterface $role_storage) {
    parent::__construct($config_factory);
    $this->roleStorage = $role_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity.manager')->getStorage('user_role')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hawk_auth_config';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['hawk.roles'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /**
     * @var RoleInterface[] $roles
     */
    $roles = $this->roleStorage->loadMultiple();
    $config = $this->config('hawk.roles');

    $form['roles'] = [
      '#type' => 'details',
      '#title' => t('Role Limitations'),
      '#description' => t('Maximum number of credentials an user belonging to each role can have, if an user has ' .
                          'multiple roles, the maximum value is taken. Set to 0 for no limit.'),
      '#open' => TRUE,
    ];

    foreach ($roles as $role) {
      if ($role->hasPermission('administer hawk') || !$role->hasPermission('access own hawk credentials')) {
        continue;
      }

      $form['roles']['role_' . $role->id()] = [
        '#type' => 'number',
        '#title' => SafeMarkup::checkPlain($role->label()),
        '#default_value' => $config->get('limit.' . $role->id()) ? $config->get('limit.' . $role->id()) : 0,
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /**
     * @var RoleInterface[] $roles
     */
    $roles = $this->roleStorage->loadMultiple();
    $role_limits = [];

    foreach ($roles as $role) {
      if (ctype_digit($form_state->getValue('role_' . $role->id()))) {
        $role_limits[$role->id()] = (int) $form_state->getValue('role_' . $role->id());
      }
      else {
        $role_limits[$role->id()] = 0;
      }
    }

    foreach ($role_limits as $role => $limit) {
      $this->config('hawk.roles')->set('limit.' . $role, $limit);
    }
    $this->config('hawk.roles')->save();
    parent::submitForm($form, $form_state);
  }

}