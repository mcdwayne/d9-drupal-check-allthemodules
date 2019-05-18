<?php

namespace Drupal\protect_before_launch;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * {@inheritdoc}
 */
class Configuration {

  /**
   * Config storage key.
   */
  const CONFIG_KEY = 'protect_before_launch.settings';

  /**
   * Always disabled.
   */
  const PROTECT_DISABLED = 0;

  /**
   * Always enabled.
   */
  const PROTECT_ENABLED = 1;

  /**
   * Enabled only when env variable is set.
   */
  const PROTECT_ENV_ENABLED = 2;

  /**
   * Simple authenticate.
   */
  const AUTH_SIMPLE = 1;

  /**
   * Drupal authenticate.
   */
  const AUTH_DRUPAL = 2;

  /**
   * Set the config hash algorithm.
   */
  const PASSWORD_HASH_METHOD = PASSWORD_BCRYPT;

  /**
   * ConfigFactory for config storage.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Editable config.
   *
   * @var \Drupal\Core\Config\ConfigBase
   */
  protected $configEditable;

  /**
   * Immutable config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $configImmutable;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Drupal config factory.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->configFactory = $configFactory;
  }

  /**
   * Get editable config.
   *
   * @return \Drupal\Core\Config\Config|\Drupal\Core\Config\ConfigBase
   *   Editable config.
   */
  protected function getEditableConfig() {
    if (empty($this->configEditable)) {
      $this->configEditable = $this->configFactory->getEditable(self::CONFIG_KEY);
    }

    return $this->configEditable;
  }

  /**
   * Get immutable config.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   Immutable config.
   */
  protected function getImmutableConfig() {
    if (empty($this->configImmutable)) {
      $this->configImmutable = $this->configFactory->get(self::CONFIG_KEY);
    }

    return $this->configImmutable;
  }

  /**
   * Generic getter config values.
   *
   * @param string $key
   *   Config key.
   *
   * @return array|mixed|null
   *   Config value.
   */
  protected function get($key) {
    return $this->getImmutableConfig()->get($key);
  }

  /**
   * Generic setter config values.
   *
   * @param string $key
   *   Config key.
   * @param array|mixed|null $value
   *   Config value.
   *
   * @return $this
   *   Configuration
   */
  protected function set($key, $value) {
    $this->getEditableConfig()->set($key, $value)->save();

    return $this;
  }

  /**
   * Set and save the username.
   *
   * @param string $username
   *   Username.
   *
   * @return $this
   *   Configuration
   */
  public function setUsername($username) {
    $this->set('username', $username);
    return $this;
  }

  /**
   * Get the username.
   *
   * @return string|null
   *   Public function getUsername string null.
   */
  public function getUsername() {
    return $this->get('username');
  }

  /**
   * Set, hash and save the password.
   *
   * @param string $password
   *   Password.
   *
   * @return $this
   *   Configuration
   */
  public function setPassword($password) {
    if (!empty($password)) {
      $this->set('password', password_hash($password, self::PASSWORD_HASH_METHOD));
    }

    return $this;
  }

  /**
   * Get the hashed password.
   *
   * @return string|null
   *   Password
   */
  public function getPassword() {
    return $this->get('password');
  }

  /**
   * Set and save the realm.
   *
   * @param mixed $realm
   *   Realm.
   *
   * @return $this
   *   Configuration
   */
  public function setRealm($realm) {
    $this->set('realm', $realm);
    return $this;

  }

  /**
   * Get the Realm.
   *
   * @return string|null
   *   Realm.
   */
  public function getRealm() {
    return str_replace('"', '\"', $this->get('realm'));
  }

  /**
   * Set protect status.
   *
   * @param mixed $protect
   *   Protect setting.
   *
   * @return $this
   *   Configuration
   */
  public function setProtect($protect) {
    $this->assertValidOption($protect, [
      self::PROTECT_ENABLED,
      self::PROTECT_DISABLED,
      self::PROTECT_ENV_ENABLED
    ]);
    $this->set('protect', $protect);
    return $this;
  }

  /**
   * Get protect status.
   *
   * @return int
   *   Protect type
   */
  public function getProtect() {
    return $this->get('protect');
  }

  /**
   * Set denied content.
   *
   * @param string $content
   *   Content.
   *
   * @return $this
   *   Configuration
   */
  public function setContent($content) {
    $this->set('content', $content);
    return $this;
  }

  /**
   * Get the denied content.
   *
   * @return string|null
   *   Content.
   */
  public function getContent() {
    return $this->get('content');
  }

  /**
   * Set the Environment Key.
   *
   * @param string $environmentKey
   *   Environment key.
   *
   * @return $this
   *   Configuration
   */
  public function setEnvironmentKey($environmentKey) {
    $this->set('environment_key', trim($environmentKey));
    return $this;
  }

  /**
   * Get Environment Key.
   *
   * @return string|null
   *   Environment key.
   */
  public function getEnvironmentKey() {
    return $this->get('environment_key');
  }

  /**
   * Set the Environment Value.
   *
   * @param string $environmentValue
   *   Environment value.
   *
   * @return $this
   *   Configuration
   */
  public function setEnvironmentValue($environmentValue) {
    $this->set('environment_value', trim($environmentValue));
    return $this;

  }

  /**
   * Get Environment Value.
   *
   * @return string|null
   *   Environment value.
   */
  public function getEnvironmentValue() {
    return $this->get('environment_value');
  }

  /**
   * Set the Authentication Value.
   *
   * @param string $authenticationType
   *   Authentication Type.
   *
   * @return $this
   *   Configuration
   */
  public function setAuthenticationType($authenticationType) {
    $this->assertValidOption($authenticationType, [
      self::AUTH_SIMPLE,
      self::AUTH_DRUPAL
    ]);
    $this->set('authentication_type', $authenticationType);
    return $this;
  }

  /**
   * Assert the chosen option is valid.
   *
   * @param string $value
   *   User input.
   * @param array $options
   *   Allowed list of options.
   */
  private function assertValidOption($value, array $options) {
    if (!in_array((int) $value, $options, TRUE)) {
      throw new \InvalidArgumentException('Unsupported option');
    }
  }

  /**
   * Get Authentication Value.
   *
   * @return string|null
   *   Authentication Type.
   */
  public function getAuthenticationType() {
    return $this->get('authentication_type');
  }

  /**
   * Set exclude paths.
   *
   * @param string $paths
   *   Exclude Paths.
   *
   * @return $this
   *   Configuration
   */
  public function setExcludePaths($paths) {
    $this->set('exclude_paths', str_replace("\r", '', $paths));
    return $this;
  }

  /**
   * Get the exclude paths as text.
   *
   * @return string|null
   *   Exclude Paths.
   */
  public function getExcludePathsText() {
    return $this->get('exclude_paths');
  }

  /**
   * Get the exclude paths as an array.
   *
   * @return array
   *   Configuration
   */
  public function getExcludePaths() {
    $paths = [];
    foreach (explode(PHP_EOL, $this->getExcludePathsText()) as $path) {
      if (!empty(trim($path))) {
        $paths[] = $path;
      }
    }
    return $paths;
  }

  /**
   * Verify username and password against saved credentials.
   *
   * @param string $username
   *   Username.
   * @param string $password
   *   Password.
   *
   * @return bool
   *   Validation result.
   */
  public function validateCredentials($username, $password) {
    if (empty($username)
      || empty($password)
      || empty($this->getUsername())
      || empty($this->getPassword())
      || !hash_equals($this->getUsername(), $username)
      || !password_verify($password, $this->getPassword())
    ) {
      return FALSE;
    }
    return TRUE;
  }

}
