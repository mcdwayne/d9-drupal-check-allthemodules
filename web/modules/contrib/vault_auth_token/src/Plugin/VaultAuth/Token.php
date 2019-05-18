<?php

namespace Drupal\vault_auth_token\Plugin\VaultAuth;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\vault\Plugin\VaultAuthBase;
use Drupal\vault\Plugin\VaultPluginFormInterface;
use Vault\AuthenticationStrategies\TokenAuthenticationStrategy;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a token-based authentication strategy for the vault client.
 *
 * @VaultAuth(
 *   id = "token",
 *   label = "Vault Token",
 *   description = @Translation("This authentication strategy uses a static token."),
 * )
 */
class Token extends VaultAuthBase implements ContainerFactoryPluginInterface, VaultPluginFormInterface {

  /**
   * The token used to authenticate against Vault.
   *
   * @var string
   */
  protected $token;

  /**
   * Sets $token property.
   *
   * @var string $token
   *
   * @return self
   *   Current object.
   */
  public function setToken(string $token) {
    $this->token = $token;
    return $this;
  }

  /**
   * Gets $token property.
   *
   * @return string
   *   Authentication token.
   */
  public function getToken() {
    return $this->token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var self $instance */
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $config = $instance->getConfigFactory()->get('vault_auth_token.settings');
    if ($key_id = $config->get('token_key_id')) {
      $token = \Drupal::service('key.repository')->getKey($key_id)->getKeyValue();
      $instance->setToken($token);
    }

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthenticationStrategy() {
    $authStrategy = new TokenAuthenticationStrategy($this->getToken());
    return $authStrategy;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();
    $form['token_key_id'] = [
      '#type' => 'key_select',
      '#title' => \Drupal::translation()->translate('Vault Token'),
      '#key_filters' => ['type' => 'authentication'],
      '#default_value' => $config['token_key_id'],
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
    $config = $this->getConfigFactory()->getEditable('vault_auth_token.settings');

    return [
      'token_key_id' => $config->get('token_key_id'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $config = $this->getConfigFactory()->getEditable('vault_auth_token.settings');
    $config
      ->set('token_key_id', $configuration['token_key_id'])
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'token_key_id' => NULL,
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
