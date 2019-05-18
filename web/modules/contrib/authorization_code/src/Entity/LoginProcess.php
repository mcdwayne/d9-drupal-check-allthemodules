<?php

namespace Drupal\authorization_code\Entity;

use Drupal\authorization_code\Exceptions\InvalidCodeException;
use Drupal\authorization_code\Exceptions\InvalidLoginProcessConfiguration;
use Drupal\authorization_code\Exceptions\IpFloodException;
use Drupal\authorization_code\Exceptions\UserFloodException;
use Drupal\authorization_code\Exceptions\UserNotFoundException;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;

/**
 * Login process config entity.
 *
 * @ConfigEntityType(
 *   id = "login_process",
 *   label = @Translation("Login Process"),
 *   label_collection = @Translation("Login Processes"),
 *   label_singular = @Translation("Login Process"),
 *   label_plural = @Translation("Login Processes"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Login Process",
 *     plural = "@count Login Processes",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\authorization_code\LoginProcessListBuilder",
 *     "form" = {
 *       "add" = "Drupal\authorization_code\Form\LoginProcessForm",
 *       "edit" = "Drupal\authorization_code\Form\LoginProcessForm",
 *       "delete" = "Drupal\authorization_code\Form\LoginProcessDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "login_process",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *     "code_generator",
 *     "user_identifier",
 *     "code_sender",
 *   },
 *   links = {
 *     "canonical" =
 *   "/admin/config/people/authorization_code/login_process/{login_process}",
 *     "add-form" =
 *   "/admin/config/people/authorization_code/login_process/add",
 *     "edit-form" =
 *   "/admin/config/people/authorization_code/login_process/{login_process}/edit",
 *     "delete-form" =
 *   "/admin/config/people/login_process/authorization_code/{login_process}/delete",
 *     "collection" = "/admin/config/people/authorization_code/login_process"
 *   }
 * )
 */
class LoginProcess extends ConfigEntityBase implements ConfigEntityInterface, EntityWithPluginCollectionInterface {

  const PLUGIN_TYPES = ['user_identifier', 'code_generator', 'code_sender'];

  /**
   * The plugin collections array.
   *
   * @var \Drupal\Component\Plugin\LazyPluginCollection[]
   */
  private $pluginCollections = [];

  /**
   * The client IP.
   *
   * @return string
   *   The client IP.
   */
  private static function getClientIpOrUnknown(): string {
    return \Drupal::request()->getClientIp() ?: 'Unknown';
  }

  /**
   * Starts the login process for an identifier.
   *
   * @param mixed $identifier
   *   The user identifier.
   *
   * @throws \Drupal\authorization_code\Exceptions\FailedToSaveCodeException
   * @throws \Drupal\authorization_code\Exceptions\FailedToSendCodeException
   * @throws \Drupal\authorization_code\Exceptions\UserNotFoundException
   * @throws \Drupal\authorization_code\Exceptions\UserFloodException
   * @throws \Drupal\authorization_code\Exceptions\InvalidLoginProcessConfiguration
   * @throws \Drupal\authorization_code\Exceptions\IpFloodException
   */
  public function startLoginProcess($identifier) {
    $this->throwIfFloodGatesAreUp($identifier);

    try {
      $user = $this->loadUserOrThrowException($identifier);
      $code = $this->getPluginOrThrowException('code_generator')->generate();
      static::codeRepository()->saveCode($this, $user, $code);
      $this->getPluginOrThrowException('code_sender')->sendCode($user, $code);
    }
    catch (UserNotFoundException $e) {
      $this->registerIpForFailedLoginAttempt();
      throw $e;
    }
  }

  /**
   * Registers the client IP for a failed login attempt.
   */
  private function registerIpForFailedLoginAttempt() {
    \Drupal::flood()->register(
      'authorization_code.failed_login_ip',
      static::settings()->get('ip_flood_threshold'),
      static::settings()->get('ip_flood_window'));
  }

  /**
   * Throws an exception if IP or user flood gates are up.
   *
   * @param mixed $identifier
   *   The user identifier.
   *
   * @throws \Drupal\authorization_code\Exceptions\IpFloodException
   * @throws \Drupal\authorization_code\Exceptions\UserFloodException
   */
  private function throwIfFloodGatesAreUp($identifier) {
    if (!$this->isAllowedByIpFloodGate()) {
      throw new IpFloodException(static::getClientIpOrUnknown());
    }
    if (!$this->isAllowedByUserFloodGate($identifier)) {
      throw new UserFloodException($this, $identifier);
    }
  }

  /**
   * Is this IP allowed by the ip flood gate.
   *
   * @return bool
   *   True if the ip is allowed, false otherwise.
   */
  public function isAllowedByIpFloodGate(): bool {
    return \Drupal::flood()->isAllowed(
      'authorization_code.failed_login_ip',
      max(1, static::settings()->get('ip_flood_threshold') ?: 1),
      static::settings()->get('ip_flood_window') ?: 3600);
  }

  /**
   * Is this IP allowed by the ip flood gate.
   *
   * @param mixed $identifier
   *   The user identifier.
   *
   * @return bool
   *   True if the ip is allowed, false otherwise.
   */
  public function isAllowedByUserFloodGate($identifier): bool {
    return \Drupal::flood()->isAllowed(
      'authorization_code.failed_login_user',
      max(1, static::settings()->get('user_flood_threshold') ?: 1),
      static::settings()->get('user_flood_window') ?: 3600,
      $this->id() . ':' . $identifier);
  }

  /**
   * Loads the user or throws an exception.
   *
   * @param string $identifier
   *   The user identifier.
   *
   * @return \Drupal\user\UserInterface
   *   The user.
   *
   * @throws \Drupal\authorization_code\Exceptions\InvalidLoginProcessConfiguration
   * @throws \Drupal\authorization_code\Exceptions\UserNotFoundException
   */
  private function loadUserOrThrowException($identifier): UserInterface {
    $user = $this->getPluginOrThrowException('user_identifier')
      ->loadUser($identifier);
    if (empty($user)) {
      throw new UserNotFoundException($identifier);
    }
    return $user;
  }

  /**
   * Is the provided code valid.
   *
   * @param mixed $identifier
   *   The user identifier.
   * @param string $code
   *   The authorization code to verify.
   *
   * @return bool
   *   Is the provided code valid?
   *
   * @throws \Drupal\authorization_code\Exceptions\UserNotFoundException
   * @throws \Drupal\authorization_code\Exceptions\InvalidLoginProcessConfiguration
   */
  public function isValidCode($identifier, string $code): bool {
    $user = $this->loadUserOrThrowException($identifier);
    return static::codeRepository()->isValidCode($this, $user, $code);
  }

  /**
   * Validates the code.
   *
   * If the code fails validation, register this as a failed login attempt and
   * throw an exception.
   *
   * @param mixed $identifier
   *   The user identifier.
   * @param string $code
   *   The code to validate.
   *
   * @throws \Drupal\authorization_code\Exceptions\InvalidLoginProcessConfiguration
   * @throws \Drupal\authorization_code\Exceptions\UserNotFoundException
   * @throws \Drupal\authorization_code\Exceptions\InvalidCodeException
   */
  public function throwAndRegisterIfInvalidCode($identifier, string $code) {
    try {
      if (!$this->isValidCode($identifier, $code)) {
        $this->registerFailedLoginAttempt($identifier);
        throw new InvalidCodeException();
      }
      return;
    }
    catch (UserNotFoundException $e) {
      $this->registerIpForFailedLoginAttempt();
      throw $e;
    }
  }

  /**
   * Registers a failed login attempt.
   *
   * @param mixed $identifier
   *   The user identifier.
   */
  private function registerFailedLoginAttempt($identifier) {
    $this->registerIpForFailedLoginAttempt();
    $this->registerUserForFailedLoginAttempt($identifier);
  }

  /**
   * Completes the login process.
   *
   * @param mixed $identifier
   *   The user identifier.
   * @param string $code
   *   The authorization code to verify.
   *
   * @return \Drupal\user\UserInterface
   *   The logged in user.
   *
   * @throws \Drupal\authorization_code\Exceptions\InvalidCodeException
   * @throws \Drupal\authorization_code\Exceptions\InvalidLoginProcessConfiguration
   * @throws \Drupal\authorization_code\Exceptions\IpFloodException
   * @throws \Drupal\authorization_code\Exceptions\UserFloodException
   * @throws \Drupal\authorization_code\Exceptions\UserNotFoundException
   */
  public function completeLoginProcess($identifier, string $code) {
    $this->throwIfFloodGatesAreUp($identifier);
    $user = $this->loadUserOrThrowException($identifier);
    if (static::codeRepository()->isValidCode($this, $user, $code)) {
      static::codeRepository()->deleteCode($this, $user);
      $this->clearFloodRegistry($identifier);
      user_login_finalize($user);
      return $user;
    }
    else {
      $this->registerUserForFailedLoginAttempt($identifier);
      throw new InvalidCodeException();
    }
  }

  /**
   * Clears the flood registry.
   *
   * @param mixed $identifier
   *   The user identifier.
   */
  private function clearFloodRegistry($identifier) {
    \Drupal::flood()->clear('authorization_code.failed_login_ip');
    \Drupal::flood()->clear('authorization_code.failed_login_user',
      $this->id() . ':' . $identifier);
  }

  /**
   * Registers the user for a failed login attempt.
   *
   * @param mixed $identifier
   *   The user identifier.
   */
  private function registerUserForFailedLoginAttempt($identifier) {
    \Drupal::flood()->register(
      'authorization_code.failed_login_user',
      static::settings()->get('user_flood_window'),
      $this->id() . ':' . $identifier);
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    foreach (static::PLUGIN_TYPES as $plugin_type) {
      if (empty($this->pluginCollections[$plugin_type]) && $configuration = $this->get($plugin_type)) {
        $this->pluginCollections[$plugin_type] = $this->buildPluginCollection($plugin_type, $configuration);
      }
    }
    return $this->pluginCollections;
  }

  /**
   * Builds a plugin collection.
   *
   * @param string $plugin_type
   *   The plugin type.
   * @param array $configuration
   *   The plugin configuration.
   *
   * @return \Drupal\Core\Plugin\DefaultSingleLazyPluginCollection|null
   *   The plugin collection, or NULL if an error occurred.
   */
  private function buildPluginCollection(string $plugin_type, array $configuration) {
    $plugin_collection = NULL;
    try {
      $plugin_collection = new DefaultSingleLazyPluginCollection(
        \Drupal::service("plugin.manager.$plugin_type"),
        $configuration['plugin_id'],
        $configuration
      );
    }
    catch (\Exception $e) {
      \Drupal::service('logger.channel.authorization_code')
        ->warning('Failed to create %plugin_type plugin. Exception: <pre>@exception</pre>', [
          '%plugin_type' => $plugin_type,
          '@exception' => $e->getMessage(),
        ]);
    }

    return $plugin_collection;
  }

  /**
   * Tries to get a plugin.
   *
   * @param string $plugin_type
   *   The plugin type.
   *
   * @return \Drupal\authorization_code\UserIdentifierInterface|\Drupal\authorization_code\CodeGeneratorInterface|\Drupal\authorization_code\CodeSenderInterface
   *   Either a user identifier or a code generator or a code sender plugin
   *   instance.
   *
   * @throws \Drupal\authorization_code\Exceptions\InvalidLoginProcessConfiguration
   */
  public function getPluginOrThrowException($plugin_type) {
    $plugin_id = $this->get($plugin_type)['plugin_id'];
    $plugin_collection = $this->getPluginCollections()[$plugin_type] ?? NULL;
    $plugin = $plugin_collection ? $plugin_collection->get($plugin_id) : NULL;
    if ($plugin instanceof PluginInspectionInterface && $plugin->getPluginId() == $plugin_id) {
      return $plugin;
    }
    else {
      throw new InvalidLoginProcessConfiguration(sprintf(
        'Failed to create the %s plugin with ID %s and configuration %s',
        $plugin_type, $plugin_id, print_r($this->get($plugin_type), TRUE)));
    }
  }

  /**
   * The settings config object.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   The settings config object.
   */
  private static function settings(): ImmutableConfig {
    return \Drupal::config('authorization_code.settings');
  }

  /**
   * The code repository service.
   *
   * @return \Drupal\authorization_code\CodeRepository
   *   The code repository service.
   */
  private static function codeRepository() {
    return \Drupal::service('authorization_code.code_repository');
  }

  /**
   * The logger channel.
   *
   * @return \Psr\Log\LoggerInterface
   *   The logger channel.
   */
  private static function logger(): LoggerInterface {
    return \Drupal::service('logger.channel.authorization_code');
  }

}
