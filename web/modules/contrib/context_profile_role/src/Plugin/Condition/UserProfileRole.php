<?php

namespace Drupal\context_profile_role\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'User Profile Role' condition.
 *
 * @Condition(
 *   id = "user_profile_role",
 *   label = @Translation("User Profile Role"),
 *   context = {
 *     "user_profile" = @ContextDefinition("entity:user", label = @Translation("User Profile Role"))
 *   },
 * );
 */
class UserProfileRole extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * Creates a new UserRole instance.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The entity storage.
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(EntityStorageInterface $entity_storage, array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityStorage = $entity_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('entity.manager')->getStorage('user_role'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $options = [];
    $user_roles = $this->entityStorage->loadMultiple();
    foreach ($user_roles as $role) {
      $options[$role->id()] = $role->label();
    }
    $form['roles'] = [
      '#title' => $this->t('Roles'),
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => $this->configuration['roles'],
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['roles'] = array_filter($form_state->getValue('roles'));
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    if (count($this->configuration['roles']) > 1) {
      $roles = $this->configuration['roles'];
      $last = array_pop($roles);
      $roles = implode(', ', $roles);
      return $this->t('The user role is @roles or @last', ['@roles' => $roles, '@last' => $last]);
    }
    $bundle = reset($this->configuration['roles']);
    return $this->t('The user role is @bundle', ['@bundle' => $bundle]);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    if (empty($this->configuration['roles']) && !$this->isNegated()) {
      return TRUE;
    }
    $user_profile_has_role = FALSE;
    if ($user_profile = $this->getContextValue('user_profile')) {
      $user_profile_roles = $user_profile->getRoles();
      // Search if the user profile has one of the authorized roles.
      foreach ($this->configuration['roles'] as $expected_role) {
        if (in_array($expected_role, $user_profile_roles)) {
          $user_profile_has_role = TRUE;
          break;
        }
      }
    }

    return $user_profile_has_role;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['roles' => []] + parent::defaultConfiguration();
  }

}
