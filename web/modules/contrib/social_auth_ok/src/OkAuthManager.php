<?php

namespace Drupal\social_auth_ok;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\social_auth\AuthManager\OAuth2Manager;

/**
 * Class OkAuthManager.
 */
class OkAuthManager extends OAuth2Manager {


  /**
   *
   * OK client
   *
   * @var \Max107\OAuth2\Client\Provider\Odnoklassniki
   */
  protected $client;


  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   Used for accessing configuration object factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory. 
   */
  public function __construct(ConfigFactory $configFactory, LoggerChannelFactoryInterface $logger_factory) {
      parent::__construct($configFactory->get('social_auth_ok.settings'), $logger_factory);
  }


  /**
   * {@inheritdoc}
   */
  public function authenticate() {  
    $this->setAccessToken($this->client->getAccessToken('authorization_code', ['code' => $_GET['code']]));
  }
 
  /**
   * Returns the authorization URL where user will be redirected.
   *
   * @return string|mixed
   *   Absolute authorization URL.
   */
  public function getAuthorizationUrl() { 
    $scopes = ['email', 'public_profile'];

    $extra_scopes = $this->getScopes(); 
    if ($extra_scopes) {
      if (strpos($extra_scopes, ',')) {
        $scopes = array_merge($scopes, explode(',', $extra_scopes));
      }
      else {
        $scopes[] = $extra_scopes;
      }
    }

    // we need to request email
    $scopes[] = 'GET_EMAIL';  

    // Returns the URL where user will be redirected.
    return $this->client->getAuthorizationUrl([ 
      'scope' => $scopes,  
    ]);
  }


  /**
   * Returns OAuth2 state.
   *
   * @return string
   *   The OAuth2 state.
   */
  public function getState() {
    return $this->client->getState();
  } 


  /**
   * Gets data about the user.
   *
   * @return \League\OAuth2\Client\Provider\GenericResourceOwner|array|mixed
   *   User info returned by provider.
   */
  public function getUserInfo() {
    $this->user = $this->client->getResourceOwner($this->getAccessToken());
    return $this->user;
  }


  /**
   * Request and end point.
   *
   * @param string $path
   *   The path or url to request.
   *
   * @return array|mixed
   *   Data returned by provider.
   */
  public function requestEndPoint($method, $path, $domain = NULL, array $options = []) {
    $url = $this->client->getBaseAuthorizationUrl() . $path;
    $request = $this->client->getAuthenticatedRequest('GET', $url, $this->getAccessToken());
    $response = $this->client->getResponse($request);
    return $response->getBody()->getContents();
  }
}
