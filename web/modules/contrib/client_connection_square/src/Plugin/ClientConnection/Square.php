<?php

namespace Drupal\client_connection_square\Plugin\ClientConnection;

use Drupal\client_connection\Plugin\ClientConnection\ClientConnectionBase;
use Drupal\client_connection\Plugin\ClientConnection\ClientConnectionInterface;
use Drupal\client_connection\Plugin\ClientConnection\HttpClientTrait;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use GuzzleHttp\Exception\ClientException;
use SquareConnect\ApiClient;
use SquareConnect\ApiException;
use SquareConnect\Configuration;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a default client connection example.
 *
 * @ClientConnection(
 *   id = "square",
 *   label = @Translation("Square"),
 *   description = @Translation("Make a connection to the eCommerce platform Square."),
 *   categories = {
 *     "global" = @Translation("Global")
 *   }
 * )
 */
class Square extends ClientConnectionBase implements ClientConnectionInterface, ContainerFactoryPluginInterface {

  use HttpClientTrait {
    getClient as getGuzzleClient;
  }

  /**
   * The permissions scope to request/use.
   *
   * @var array
   *
   * @todo this needs to be modifiable.
   */
  protected $permissionScope = [
    'ITEMS_READ',
    'MERCHANT_PROFILE_READ',
    'PAYMENTS_READ',
    'PAYMENTS_WRITE',
    'CUSTOMERS_READ',
    'CUSTOMERS_WRITE',
  ];

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new class instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, StateInterface $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function clientForm(array $form, FormStateInterface $form_state) {
    $form['#process'][] = [$this, 'processAccessToken'];

    $form['oauth'] = [
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#title' => $this->t('OAuth'),
    ];
    $form['oauth']['app_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Application Secret'),
      '#default_value' => $this->getConfigurationValue('app_secret', ''),
      '#description' => $this->t('You can get this by selecting your app <a href="https://connect.squareup.com/apps">here</a> and clicking on the OAuth tab.'),
      '#required' => TRUE,
    ];
    $form['oauth']['redirect_url'] = [
      '#type' => 'item',
      '#title' => $this->t('Redirect URL'),
      '#markup' => Url::fromRoute('client_connection_square.oauth.obtain', [], ['absolute' => TRUE])->toString(),
      '#description' => $this->t('Copy this URL and use it for the redirect URL field in your app OAuth settings.'),
    ];

    $form['credentials'] = [
      '#type' => 'fieldset',
      '#description' => $this->t('You can get these by selecting your app <a href="https://connect.squareup.com/apps">here</a>.'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#title' => $this->t('Credentials'),
    ];
    $form['credentials']['app_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Application Name'),
      '#default_value' => $this->getConfigurationValue('app_name', ''),
      '#required' => TRUE,
    ];

    $form['credentials']['production_app_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Application ID'),
      '#default_value' => $this->getConfigurationValue('production_app_id', ''),
      '#required' => TRUE,
    ];

    $form['sandbox'] = [
      '#type' => 'fieldset',
      '#description' => $this->t('You can get these by selecting your app <a href="https://connect.squareup.com/apps">here</a>.'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#title' => $this->t('Sandbox'),
    ];
    $form['sandbox']['sandbox_app_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sandbox Application ID'),
      '#default_value' => $this->getConfigurationValue('sandbox_app_id', ''),
      '#required' => TRUE,
    ];
    $form['sandbox']['sandbox_access_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sandbox Access Token'),
      '#default_value' => $this->getConfigurationValue('sandbox_access_token', ''),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function clientValidate(array $form, FormStateInterface $form_state) {
    // Do nothing.
  }

  /**
   * {@inheritdoc}
   */
  protected function clientSubmit(array &$form, FormStateInterface $form_state) {
    $this->configuration['app_name'] = $form_state->getValue(['credentials', 'app_name']);
    $this->configuration['app_secret'] = $form_state->getValue(['oauth', 'app_secret']);
    $this->configuration['sandbox_app_id'] = $form_state->getValue(['sandbox', 'sandbox_app_id']);
    $this->configuration['sandbox_access_token'] = $form_state->getValue(['sandbox', 'sandbox_access_token']);
    $this->configuration['production_app_id'] = $form_state->getValue(['credentials', 'production_app_id']);

    $state_token = \Drupal::csrfToken()->get();
    $this->getTempStore()->get('client_connection_square')->set($state_token, \Drupal::request()->getUri());
    $options = [
      'query' => [
        'client_id' => $this->getConfigurationValue('production_app_id'),
        'state' => $state_token,
        'scope' => implode(' ', $this->permissionScope),
      ],
    ];
    $url = Url::fromUri('https://connect.squareup.com/oauth2/authorize', $options);
    $form_state->setResponse(new TrustedRedirectResponse($url->toString()));
  }

  /**
   * Process retrieving the access token.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   Return the modified form array.
   */
  public function processAccessToken(array $form, FormStateInterface $form_state) {
    $code = $this->getRequest()->query->get('code');
    if (!empty($code) && !empty($this->getConfigurationValue('production_app_id')) && !empty($this->getConfigurationValue('app_secret'))) {
      $this->retrieveAccessToken($code);
    }
    else {
      drupal_set_message($this->t('After clicking save you will be redirected to Square to sign in and connect your account.'), 'warning');
    }
    return $form;
  }

  /**
   * Gets the user temp-store factory.
   *
   * @return \Drupal\user\PrivateTempStoreFactory
   *   The tempstore factory instance.
   */
  protected function getTempStore() {
    return \Drupal::service('user.private_tempstore');
  }

  /**
   * Gets a Square API client.
   *
   * @param string $mode
   *   The mode.
   * @param null|string $access_token_uuid
   *   (optional) A specific access token to receive. Null loads default token.
   *
   * @return \SquareConnect\ApiClient
   *   A configured API client for the Connect application.
   */
  public function getClient($mode, $access_token_uuid = NULL) {
    $config = new Configuration();
    $config->setAccessToken($this->getAccessToken($mode, $access_token_uuid));
    return new ApiClient($config);
  }

  /**
   * Gets the application name.
   *
   * @return string
   *   The application name.
   */
  public function getAppName() {
    return $this->getConfigurationValue('app_name');
  }

  /**
   * Gets the application secret.
   *
   * @return string
   *   The secret.
   */
  public function getAppSecret() {
    return $this->getConfigurationValue('app_secret');
  }

  /**
   * Gets the application ID.
   *
   * @param string $mode
   *   The mode.
   *
   * @return string
   *   The application ID.
   */
  public function getAppId($mode) {
    if ($mode == 'production') {
      return $this->getConfigurationValue('production_app_id');

    }
    return $this->getConfigurationValue('sandbox_app_id');
  }

  /**
   * Sets the current connection's access token.
   *
   * @param string $token
   *   The access token.
   * @param int $expiry
   *   The token expiry timestamp.
   * @param nuLL|string $uuid
   *   (optional) The token's related uuid.
   */
  public function setAccessToken($token, $expiry, $uuid = NULL) {
    $uuid = $uuid ? $uuid : $this->getEntity()->uuid();
    $this->state->set("client_connection_square.{$uuid}.production_access_token", $token);
    $this->state->set("client_connection_square.{$uuid}.production_access_token_expiry", $expiry);
  }

  /**
   * Gets the access token.
   *
   * @param string $mode
   *   The mode.
   * @param null|string $uuid
   *   (optional) A specific access token to retrieve.
   *
   * @return string
   *   The access token.
   */
  public function getAccessToken($mode, $uuid = NULL) {
    if ($mode == 'production') {
      $uuid = $uuid ? $uuid : $this->getEntity()->uuid();
      return $this->state->get("client_connection_square.{$uuid}.production_access_token");
    }
    return $this->getConfigurationValue('sandbox_access_token');
  }

  /**
   * Gets the access token expiration timestamp.
   *
   * @param string $mode
   *   The mode.
   * @param null|string $uuid
   *   (optional) A specific access token to retrieve.
   *
   * @return int
   *   The expiration timestamp. Or -1 if sandbox.
   */
  public function getAccessTokenExpiration($mode, $uuid = NULL) {
    if ($mode == 'production') {
      $uuid = $uuid ? $uuid : $this->getEntity()->uuid();
      return $this->state->get("client_connection_square.{$uuid}.production_access_token_expiry");
    }

    return -1;
  }

  /**
   * Retrieves and saves this client connections oauth access token.
   *
   * @param mixed $code
   *   The code retrieved from the authorization request.
   * @param null|string $uuid
   *   (optional) A specific access token to retrieve as.
   */
  public function retrieveAccessToken($code, $uuid = NULL) {
    if (!empty($code) && !empty($this->getConfigurationValue('production_app_id')) && !empty($this->getConfigurationValue('app_secret'))) {
      // We can send this request only once to square.
      $response = $this->getGuzzleClient()->post('https://connect.squareup.com/oauth2/token', [
        'json' => [
          'client_id' => $this->getConfigurationValue('production_app_id'),
          'client_secret' => $this->getConfigurationValue('app_secret'),
          'code' => $code,
        ],
      ]);
      $response_body = Json::decode($response->getBody());
      if (!empty($response_body['access_token'])) {
        $uuid = $uuid ? $uuid : $this->getEntity()->uuid();
        $this->setAccessToken($response_body['access_token'], strtotime($response_body['expires_at']), $uuid);
        drupal_set_message($this->t('Your Drupal site and Square have been successfully connected.'));
      }
    }
  }

  /**
   * Updates this client connections oauth access token.
   *
   * @param null|string $uuid
   *   (optional) A specific access token to update.
   */
  public function updateAccessToken($uuid = NULL) {
    $logger = \Drupal::logger('commerce_square');
    $uuid = $uuid ? $uuid : $this->getEntity()->uuid();
    if (empty($this->getAccessToken('production', $uuid))) {
      $logger->debug('No access token, skipping');
      return;
    }
    $access_token_expiry = $this->getAccessTokenExpiration('production', $uuid);
    if (!empty($access_token_expiry)) {
      // We can send this request only once to square.
      try {
        $response = $this->getGuzzleClient()->post('https://connect.squareup.com/oauth2/clients/' . $this->getAppId('production') . '/access-token/renew', [
          'json' => [
            'access_token' => $this->getAccessToken('production', $uuid),
          ],
          'headers' => [
            'Authorization' => 'Client ' . $this->getAppSecret(),
            'Accept' => 'application/json',
          ],
        ]);
        $response_body = Json::decode($response->getBody());
        $logger->debug(Json::encode($response_body));
        if (!empty($response_body['access_token'])) {
          $this->setAccessToken($response_body['access_token'], strtotime($response_body['expires_at']), $uuid);
        }
      }
      catch (ApiException $e) {
        $logger->error(t('Error when renewing access token: :s', [':s' => $e->getMessage()]));
      }
      catch (ClientException $e) {
        $logger->error(t('Error when renewing access token: :s', [':s' => $e->getMessage()]));
      }
      catch (\Exception $e) {
        $logger->error(t('Error when renewing access token: :s', [':s' => $e->getMessage()]));
      }
    }
  }

}
