<?php

namespace Drupal\memory_limit_policy_role\Plugin\MemoryLimitConstraint;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\memory_limit_policy\MemoryLimitConstraintBase;
use Drupal\user\RoleStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure the memory limit based on role.
 *
 * @MemoryLimitConstraint(
 *   id = "role",
 *   title = @Translation("Role"),
 *   description = @Translation("Provide a list of roles for which the memory limit must be overridden.")
 * )
 */
class Role extends MemoryLimitConstraintBase implements ContainerFactoryPluginInterface {

  /**
   * The role storage.
   *
   * @var \Drupal\user\RoleStorageInterface
   */
  protected $roleStorage;

  /**
   * Proxy for the current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $current_user;

  /**
   * Constructs a Role MemoryLimitConstraint object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\user\RoleStorageInterface $role_storage
   *   The role storage.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user account.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RoleStorageInterface $role_storage, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->roleStorage = $role_storage;
    $this->current_user = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager')->getStorage('user_role'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $options = [];
    foreach ($this->roleStorage->loadMultiple() as $role) {
      $options[$role->id()] = $role->label();
    }

    $form['roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles'),
      '#description' => $this->t('Select roles for which the memory limit must be overridden.'),
      '#options' => $options,
      '#default_value' => $this->getConfiguration()['roles'] ?? [],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['roles'] = array_filter($form_state->getValue('roles'));
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $roles = $this->roleStorage->loadMultiple();
    $selected_roles = [];
    foreach ($this->configuration['roles'] as $role) {
      if (isset($roles[$role])) {
        $selected_roles[] = $roles[$role]->label();
      }
    }

    return $this->t('Roles: @roles', ['@roles' => implode(', ', $selected_roles)]);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    return (bool) count(array_intersect(
      $this->configuration['roles'],
      $this->current_user->getRoles()
    ));
  }

}
