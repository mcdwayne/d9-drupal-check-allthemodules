<?php

namespace Drupal\open_connect\Plugin\OpenConnect\Provider;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Url;
use Drupal\open_connect\Exception\OpenConnectException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class ProviderBase extends PluginBase implements ProviderInterface, ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The http client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a new ProviderBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \GuzzleHttp\Client $http_client
   *   The http client object.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, Client $http_client, LanguageManagerInterface $language_manager, LoggerChannelFactoryInterface $logger_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->httpClient = $http_client;
    $this->languageManager = $language_manager;
    $this->logger = $logger_factory->get('open_connect');
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('http_client'),
      $container->get('language_manager'),
      $container->get('logger.factory')
    );
  }

  public function calculateDependencies() {
    // TODO: dependencies
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'mode' => 'test',
      'client_id' => '',
      'client_secret' => '',
      'scope' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep($this->defaultConfiguration(), $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Mode'),
      '#options' => [
        'test' => t('Test'),
        'live' => t('Live'),
      ],
      '#default_value' => $this->configuration['mode'],
    ];
    $form['client_id'] = [
      '#title' => $this->t('Client ID'),
      '#type' => 'textfield',
      '#maxlength' => 255,
      '#default_value' => $this->configuration['client_id'],
    ];
    $form['client_secret'] = [
      '#title' => $this->t('Client Secret'),
      '#type' => 'textfield',
      '#maxlength' => 255,
      '#default_value' => $this->configuration['client_secret'],
    ];
    $form['scope'] = [
      '#title' => $this->t('Scope'),
      '#type' => 'textfield',
      '#maxlength' => 255,
      '#default_value' => $this->configuration['scope'],
    ];
    $form['redirect_uri'] = [
      '#title' => $this->t('Redirect URI'),
      '#type' => 'item',
      '#input' => FALSE,
      '#markup' => $this->getRedirectUri(),
    ];
    if ($homepage = $this->pluginDefinition['homepage']) {
      $params = [
        '@homepage' => $homepage,
        '@description' => $this->pluginDefinition['description'],
      ];
      $form['description'] = [
        '#markup' => '<div class="description">' . $this->t('Set up your app on <a href="@homepage" target="_blank">@description</a>.', $params) . '</div>',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (empty($values['client_id'])) {
      $form_state->setError($form['client_id'], 'Client ID cannot be empty.');
    }
    if (empty($values['client_secret'])) {
      $form_state->setError($form['client_secret'], 'Client secret cannot be empty.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->configuration['mode'] = $values['mode'];
    $this->configuration['client_id'] = $values['client_id'];
    $this->configuration['client_secret'] = $values['client_secret'];
    $this->configuration['scope'] = $values['scope'];
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthorizeUrl($state) {
    $this->assertConfiguration();

    $options = [
      'query' => [
        'response_type' => 'code',
        $this->getKey('client_id') => $this->configuration['client_id'],
        'redirect_uri' => $this->getRedirectUri(),
        'state' => $state,
        'scope' => $this->configuration['scope'],
      ],
    ];
    $options['query'] = array_filter($options['query']);
    $this->processRedirectUrlOptions($options);
    return Url::fromUri($this->getUrl('authorization'), $options);
  }

  /**
   * Processes the redirect url options.
   *
   * @param array $options
   *   The url options.
   */
  protected function processRedirectUrlOptions(array &$options) {
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate($code) {
    $this->assertConfiguration();
    $this->logResponse('Authorization code', ['code' => $code]);

    // Fetch authorization token.
    $token_response = $this->fetchToken($code);

    // Get openid from the token response or fetch it if not found.
    // Providers like WeChat and Weibo return an openid in the token response.
    $openid_key = $this->getKey('openid');
    $openid = isset($token_response[$openid_key]) ? $token_response[$openid_key] : $this->fetchOpenid($token_response['access_token']);
    // WeChat providers may return an unionid.
    $unionid = isset($token_response['unionid']) ? $token_response['unionid'] : '';

    /** @var \Drupal\open_connect\OpenConnectStorageInterface $open_connect_storage */
    $open_connect_storage = $this->entityTypeManager->getStorage('open_connect');
    $open_connect = $open_connect_storage->loadByOpenid($this->pluginId, $openid);
    if (!$open_connect) {
      // Try to load by unionid.
      if($unionid && $open_connect = $open_connect_storage->loadByUnionid($unionid)) {
        // Get the existing user.
        $user = $open_connect->getAccount();
      }
      else {
        // Create a new user to authenticate.
        $user = $this->createUser();
      }

      // Create an open connect entity with the new provider, openid and
      // possible unionid for the existing user or a new user.
      $open_connect = $open_connect_storage->create([
        'provider' => $this->pluginId,
        'openid' => $openid,
        'unionid' => $unionid,
        'uid' => $user->id(),
      ]);
      $open_connect->save();
    }
    elseif ($unionid && !$open_connect->getUnionid()) {
      // Save the unionid.
      $open_connect->setUnionid($unionid)->save();
    }

    return $open_connect->getAccount();
  }

  /**
   * Configuration assertion.
   */
  private function assertConfiguration() {
    if (empty($this->configuration['client_id'])) {
      throw new \InvalidArgumentException('Client ID is not set.');
    }
    if (empty($this->configuration['client_secret'])) {
      throw new \InvalidArgumentException('Client secret is not set.');
    }
  }

  /**
   * Fetches an authorization token by the given authorization code.
   *
   * @param string $code
   *   The authorization code.
   *
   * @return array
   *   An array of response data values.
   *
   * @throws \Drupal\open_connect\Exception\OpenConnectException
   *   Thrown when the http request fails or the response is failed.
   */
  protected function fetchToken($code) {
    try {
      // Fetch authorization token.
      $token_response = $this->doFetchToken($this->getUrl('access_token'), [
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => $this->getRedirectUri(),
        $this->getKey('client_id') => $this->configuration['client_id'],
        $this->getKey('client_secret') => $this->configuration['client_secret'],
      ]);
      $this->logResponse('Fetch token', $token_response);
    }
    catch (RequestException $e) {
      throw new OpenConnectException(sprintf('%s Could not fetch authorization token: %s', $this->pluginId, $e->getMessage()), $e->getCode(), $e);
    }

    if (!$this->isResponseSuccessful($token_response)) {
      throw new OpenConnectException($this->getResponseError($token_response));
    }

    return $token_response;
  }

  /**
   * Preforms a provider-specific fetching token.
   *
   * @param string $url
   *   The api url.
   * @param array $params
   *   The request parameters.
   *
   * @return array
   *   The token array
   *
   * @throws \GuzzleHttp\Exception\RequestException
   *   Thrown when the http request fails.
   */
  protected function doFetchToken($url, array $params) {
    $response = $this->httpClient->post($url, [
      'form_params' => $params,
    ]);

    // getBody() returns an instance of Psr\Http\Message\StreamInterface.
    // @see http://docs.guzzlephp.org/en/latest/psr7.html#body
    return \GuzzleHttp\json_decode($response->getBody(), TRUE);
  }

  /**
   * Fetches openid.
   *
   * Note: currently only QQ needs to fetch the openid.
   *
   * @param string $access_token
   *   The access token.
   *
   * @return string
   *   The openid.
   *
   * @throws \Drupal\open_connect\Exception\OpenConnectException
   *   Thrown when the http request fails or the response is failed.
   */
  protected function fetchOpenid($access_token) {
    try {
      $openid_response = $this->doFetchOpenid($this->getUrl('openid'), [
        'access_token' => $access_token,
      ]);
      $this->logResponse('Fetch openid', $openid_response);
    }
    catch (RequestException $e) {
      throw new OpenConnectException(sprintf('%s Could not fetch openid: %s', $this->pluginId, $e->getMessage()), $e->getCode(), $e);
    }

    // Throws when the transaction fails for any reason, see SupportsRefundsInterface.
    if (!$this->isResponseSuccessful($openid_response)) {
      throw new OpenConnectException($this->getResponseError($openid_response));
    }

    $openid_key = $this->getKey('openid');
    return $openid_response[$openid_key];
  }

  /**
   * Preforms a provider-specific fetching openid.
   *
   * @param string $url
   *   The api url.
   * @param array $params
   *   The request parameters.
   *
   * @return array
   *   An array of response data values.
   *
   * @throws \GuzzleHttp\Exception\RequestException
   *   Thrown when the http request fails.
   */
  protected function doFetchOpenid($url, array $params) {
    $response = $this->httpClient->get($url, [
      'query' => $params,
    ]);
    return \GuzzleHttp\json_decode($response->getBody(), TRUE);
  }

  /**
   * Fetches the user info with the given access token and openid.
   *
   * @param string $access_token
   *   The access token.
   * @param string $openid
   *   The openid.
   *
   * @return array
   *   The user claims.
   *
   * @throws \Drupal\open_connect\Exception\OpenConnectException
   *   Thrown when the http request fails or the response is failed.
   */
  public function fetchUserInfo($access_token, $openid) {
    $openid_key = $this->getKey('openid');
    $params = [
      'access_token' => $access_token,
      $openid_key => $openid,
    ];
    return $this->doFetchUserInfo($this->getUrl('user_info'), $params);
  }

  /**
   * Preforms a provider-specific fetching token.
   *
   * @param string $url
   *   The api url.
   * @param array $params
   *   The request parameters.
   *
   * @return array
   *   The token array
   *
   * @throws \GuzzleHttp\Exception\RequestException
   *   Thrown when the http request fails.
   */
  protected function doFetchUserInfo($url, array $params) {
    $response = $this->httpClient->get($url, [
      'query' => $params,
    ]);
    return \GuzzleHttp\json_decode($response->getBody(), TRUE);
  }

  /**
   * Creates a new user.
   *
   * @return \Drupal\user\UserInterface
   *   The newly created user object.
   */
  private function createUser() {
    $user_storage = $this->entityTypeManager->getStorage('user');

    // Get a unique username.
    $name = 'u' . date('YmdHis');
    $i = 0;
    while ($user_storage->loadByProperties(['name' => $name])) {
      $name .= '_' . ++$i;
    }

    /** @var \Drupal\user\UserInterface $user */
    $user = $user_storage->create([
      'name' => $name,
      'pass' => user_password(),
      'mail' => '',
    ]);
    // Always active the new user.
    $user->activate();
    $user->save();

    return $user;
  }

  /**
   * Gets a url for the the given api.
   *
   * @param string $api
   *   The api.
   *
   * @return string|bool
   *   The url, or FALSE if not found.
   */
  protected function getUrl($api) {
    $urls = $this->pluginDefinition['urls'];
    return isset($urls[$api]) ? $urls[$api]: FALSE;
  }

  /**
   * Gets a specific key.
   *
   * @param string $key
   *   The name of the key to return.
   *
   * @return string|bool
   *   The key, or FALSE if it does not exist.
   */
  protected function getKey($key) {
    $keys = $this->pluginDefinition['keys'];
    return isset($keys[$key]) ? $keys[$key] : FALSE;
  }

  /**
   * Gets the redirect uri.
   *
   * @return string
   */
  private function getRedirectUri() {
    // Redirect uri
    return Url::fromRoute('open_connect.authenticate', [
      'open_connect_provider' => $this->pluginId,
    ], [
      // 'query' => $redirect_uri_query,
      'absolute' => TRUE,
      'language' => $this->languageManager->getLanguage(LanguageInterface::LANGCODE_NOT_APPLICABLE),
    ])->toString();
  }

  /**
   * Logs response.
   *
   * @param string $operation
   *   The operation
   * @param array $response
   *   An array of response data.
   */
  private function logResponse($operation, array $response) {
    if (empty($response)) {
      return;
    }
    // Log response if the data represents an failure, or the plugin is not in
    // live mode.
    $successful = $this->isResponseSuccessful($response);
    if (!$successful || $this->configuration['mode'] !== 'live') {
      $level = $successful ? 'debug' : 'warning';
      $this->logger->$level('@provider @operation: <pre>@response</pre>', [
        '@provider' => $this->pluginId,
        '@operation' => $operation,
        '@response' => print_r($response, TRUE),
      ]);
    }
  }

  /**
   * Whether the response represents a successful data.
   *
   * @param array $response
   *   The response data.
   *
   * @return bool
   *   True if the existing payment is available for reuse, FALSE otherwise.
   */
  abstract protected function isResponseSuccessful(array $response);

  /**
   * Gets the error message from the given response data.
   *
   * @param array $response
   *   The response data
   *
   * @return string
   *   The error message.
   */
  abstract protected function getResponseError(array $response);

}
