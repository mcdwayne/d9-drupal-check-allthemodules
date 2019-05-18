<?php

namespace Drupal\oauth2_client\Plugin\Oauth2Client;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\oauth2_client\Exception\Oauth2ClientPluginMissingKeyException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Oauth2Client plugins.
 */
abstract class Oauth2ClientPluginBase extends PluginBase implements Oauth2ClientPluginInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a Oauth2ClientPluginBase object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definitions.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigFactoryInterface $configFactory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    $this->checkKeyDefined('name');

    return $this->pluginDefinition['name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    $this->checkKeyDefined('id');

    return $this->pluginDefinition['id'];
  }

  /**
   * {@inheritdoc}
   */
  public function getClientId() {
    $this->checkKeyDefined('client_id');

    return $this->pluginDefinition['client_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function getClientSecret() {
    $this->checkKeyDefined('client_secret');

    return $this->pluginDefinition['client_secret'];
  }

  /**
   * {@inheritdoc}
   */
  public function getGrantType() {
    $this->checkKeyDefined('grant_type');

    return $this->pluginDefinition['grant_type'];
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectUri() {
    $this->checkKeyDefined('redirect_uri');

    return $this->pluginDefinition['redirect_uri'];
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthorizationUri() {
    $this->checkKeyDefined('authorization_uri');

    return $this->pluginDefinition['authorization_uri'];
  }

  /**
   * {@inheritdoc}
   */
  public function getTokenUri() {
    $this->checkKeyDefined('token_uri');

    return $this->pluginDefinition['token_uri'];
  }

  /**
   * {@inheritdoc}
   */
  public function getResourceUri() {
    $this->checkKeyDefined('resource_owner_uri');

    return $this->pluginDefinition['resource_owner_uri'];
  }

  /**
   * {@inheritdoc}
   */
  public function getScopes() {
    if (!isset($this->pluginDefinition['scopes'])) {
      return [];
    }

    return $this->pluginDefinition['scopes'] ?: [];
  }

  /**
   * {@inheritdoc}
   */
  public function getScopeSeparator() {
    if (!isset($this->pluginDefinition['scope_separator'])) {
      return ',';
    }

    return $this->pluginDefinition['scope_separator'];
  }

  /**
   * {@inheritdoc}
   */
  public function getUsername() {
    $this->checkKeyDefined('username');

    return $this->pluginDefinition['username'];
  }

  /**
   * {@inheritdoc}
   */
  public function getPassword() {
    $this->checkKeyDefined('password');

    return $this->pluginDefinition['password'];
  }

  /**
   * Check that a key is defined when requested. Throw an exception if not.
   *
   * @param string $key
   *   The key to check.
   *
   * @throws \Drupal\oauth2_client\Exception\Oauth2ClientPluginMissingKeyException
   *   Thrown if the key being checked is not defined.
   */
  private function checkKeyDefined($key) {
    if (!isset($this->pluginDefinition[$key])) {
      throw new Oauth2ClientPluginMissingKeyException($key);
    }
  }

}
