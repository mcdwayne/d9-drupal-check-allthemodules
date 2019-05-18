<?php

namespace Drupal\sdk;

use Drupal\Core\Url;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\KeyValueStore\KeyValueDatabaseExpirableFactory;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\Component\Datetime\TimeInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\sdk\Entity\Sdk;
use Drupal\sdk\Entity\SdkInterface;

/**
 * Base SDK plugin.
 *
 * @property SdkPluginDefinition $pluginDefinition
 */
abstract class SdkPluginBase extends PluginBase implements ContainerFactoryPluginInterface {

  /**
   * Marker for unexpirable tokens.
   */
  const TOKEN_LIFE_UNLIMITED = -1;

  /**
   * SDK configuration.
   *
   * @var \Drupal\sdk\Entity\Sdk
   */
  private $config;
  /**
   * Instance of the "keyvalue.expirable.database" service.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface
   */
  protected $storage;
  /**
   * Instance of the "request_stack" service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;
  /**
   * Storage of configuration entities.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $configEntityStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    SdkPluginDefinition $plugin_definition,
    KeyValueExpirableFactoryInterface $key_value_storage,
    RequestStack $request_stack,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->storage = $key_value_storage;
    $this->requestStack = $request_stack;
    $this->configEntityStorage = $entity_type_manager->getStorage(SdkInterface::ENTITY_TYPE);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('keyvalue.expirable.database'),
      $container->get('request_stack'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig() {
    if (NULL === $this->config) {
      $this->config = $this->configEntityStorage->load($this->pluginId);
    }

    return $this->config;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfig(Sdk $config) {
    $this->config = $config;
  }

  /**
   * Returns an instance of SDK.
   *
   * @return object
   *   SDK instance.
   */
  abstract protected function getInstance();

  /**
   * Derive an instance of SDK.
   *
   * @return object
   *   Derived instance of SDK.
   */
  public function derive() {
    return $this->getInstance();
  }

  /**
   * Return URL to redirect to for login.
   *
   * @return string
   *   URL to redirect to for login and token obtaining.
   */
  public function loginUrl() {
    if (!$this->isLoginCallbackOverridden()) {
      throw new \RuntimeException(sprintf('The "%s" method must be overridden by "%s" class', 'loginCallback', static::class));
    }

    return '';
  }

  /**
   * Process result of visiting the login URL.
   */
  public function loginCallback() {
  }

  /**
   * Check whether "loginCallback" method has been overridden.
   *
   * @return bool
   *   A state of check.
   */
  final public function isLoginCallbackOverridden() {
    return (new \ReflectionMethod($this, 'loginCallback'))->getDeclaringClass()->getName() !== self::class;
  }

  /**
   * Get instance of configuration form.
   *
   * @return \Drupal\sdk\SdkPluginConfigurationFormBase
   *   SDK configuration form.
   *
   * @see \Drupal\sdk\Entity\Form\Sdk\DefaultForm::invoke()
   */
  final public function getConfigurationForm() {
    static $forms = [];

    $class = $this->pluginDefinition->getFormClass();

    if (empty($forms[$class])) {
      $forms[$class] = new $class($this->getConfig());
    }

    return $forms[$class];
  }

  /**
   * Returns token.
   *
   * @return mixed|null
   *   Representation of a token or NULL if it was not set.
   */
  public function getToken() {
    return $this->getStorage()->get($this->pluginId);
  }

  /**
   * Set token.
   *
   * @param object|string $value
   *   Representation of a token.
   * @param int|null $expire
   *   Expiration timestamp.
   */
  public function setToken($value, $expire = NULL) {
    if (!empty($value)) {
      if (NULL === $expire) {
        $this->getStorage()->set($this->pluginId, $value);
      }
      else {
        $this->getStorage()->setWithExpire($this->pluginId, $value, $expire - REQUEST_TIME);
      }
    }
  }

  /**
   * Returns a date when token will no longer be valid.
   *
   * @return \DateTime|null|int
   *   DateTime object of expiration, NULL if token expired or
   *   "self::TOKEN_LIFE_UNLIMITED" if token has no limitation.
   */
  public function getTokenExpiration() {
    return NULL;
  }

  /**
   * Trigger "sdk.callback" which must implement token requesting/receiving.
   *
   * @param string|Url|null $destination
   *   Destination path where user should be after processing.
   *
   * @return TrustedRedirectResponse
   *   An instance of response.
   */
  public function requestToken($destination = NULL) {
    if (empty($destination)) {
      $destination = $this->requestStack->getCurrentRequest()->getUri();
    }
    elseif ($destination instanceof Url) {
      $destination = $destination->toString();
    }

    $_SESSION['destination'] = $destination;

    return new TrustedRedirectResponse($this->loginUrl());
  }

  /**
   * Returns storage for SDK tokens.
   *
   * @return \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface
   *   Storage for SDK tokens.
   */
  private function getStorage() {
    if ($this->storage instanceof KeyValueDatabaseExpirableFactory) {
      $this->storage->garbageCollection();
    }

    return $this->storage->get('sdk_tokens_storage');
  }

}
