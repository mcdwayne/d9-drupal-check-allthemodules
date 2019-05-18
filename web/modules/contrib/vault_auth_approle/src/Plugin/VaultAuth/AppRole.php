<?php

namespace Drupal\vault_auth_approle\Plugin\VaultAuth;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\vault\Plugin\VaultAuthBase;
use Drupal\vault\Plugin\VaultPluginFormInterface;
use Vault\AuthenticationStrategies\AppRoleAuthenticationStrategy;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a approle-based authentication strategy for the vault client.
 *
 * @VaultAuth(
 *   id = "approle",
 *   label = "AppRole",
 *   description = @Translation("This authentication strategy uses an approle id and secret."),
 * )
 */
class AppRole extends VaultAuthBase implements ContainerFactoryPluginInterface, VaultPluginFormInterface {

  /**
   * The AppRole role ID.
   *
   * @var string
   */
  protected $roleId;

  /**
   * The AppRole role secret.
   *
   * @var string
   */
  protected $secretId;

  /**
   * Sets $roleId property.
   *
   * @var string $roleId
   *
   * @return self
   *   Current object.
   */
  public function setRoleId(string $roleId) {
    $this->roleId = $roleId;
    return $this;
  }

  /**
   * Gets $roleId property.
   *
   * @return string
   *   AppRole ID.
   */
  public function getRoleId() {
    return $this->roleId;
  }

  /**
   * Sets $secretId property.
   *
   * @var string $secretId
   *
   * @return self
   *   Current object.
   */
  public function setSecretId(string $secretId) {
    $this->secretId = $secretId;
    return $this;
  }

  /**
   * Gets $secretId property.
   *
   * @return string
   *   AppRole secret.
   */
  public function getSecretId() {
    return $this->secretId;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var self $instance */
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $config = $instance->getConfigFactory()->get('vault_auth_approle.settings');
    if ($role_id = $config->get('role_id')) {
      $instance->setRoleId($role_id);
    }
    if ($secret_key_id = $config->get('secret_key_id')) {
      $secret = \Drupal::service('key.repository')->getKey($secret_key_id)->getKeyValue();
      $instance->setSecretId($secret);
    }

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthenticationStrategy() {
    $authStrategy = new AppRoleAuthenticationStrategy($this->getRoleId(), $this->getSecretId());
    return $authStrategy;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();
    $form['role_id'] = [
      '#type' => 'textfield',
      '#title' => \Drupal::translation()->translate('AppRole ID'),
      '#default_value' => $config['role_id'],
    ];
    $form['secret_key_id'] = [
      '#type' => 'key_select',
      '#title' => \Drupal::translation()->translate('AppRole Secret'),
      '#key_filters' => ['type' => 'authentication'],
      '#default_value' => $config['secret_key_id'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement validateConfigurationForm() method.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement submitConfigurationForm() method.
    $this->setConfiguration($form_state->getValues());
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    $config = $this->getConfigFactory()->getEditable('vault_auth_approle.settings');

    return [
      'role_id' => $config->get('role_id'),
      'secret_key_id' => $config->get('secret_key_id'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $config = $this->getConfigFactory()->getEditable('vault_auth_approle.settings');
    $config
      ->set('role_id', $configuration['role_id'])
      ->set('secret_key_id', $configuration['secret_key_id'])
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'role_id' => NULL,
      'secret_key_id' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    // TODO: Implement calculateDependencies() method.
    return [];
  }

}
