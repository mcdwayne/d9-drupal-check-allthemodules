<?php

namespace Drupal\oauth2_server\Authentication\Provider;

use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\oauth2_server\OAuth2StorageInterface;

/**
 * Class OAuth2DrupalAuthProvider.
 *
 * @package Drupal\oauth2_server\Authentication\Provider
 */
class OAuth2DrupalAuthProvider implements AuthenticationProviderInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The OAuth2Storage.
   *
   * @var \Drupal\oauth2_server\OAuth2StorageInterface
   */
  protected $storage;

  /**
   * Constructs a HTTP basic authentication provider object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\oauth2_server\OAuth2StorageInterface $oauth2_storage
   *   The OAuth2 storage object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, OAuth2StorageInterface $oauth2_storage) {
    $this->configFactory = $config_factory;
    $this->entityManager = \Drupal::getContainer()->get('entity.manager');
    $this->storage = $oauth2_storage;
  }

  /**
   * Checks whether suitable authentication credentials are on the request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return bool
   *   TRUE if authentication credentials suitable for this provider are on the
   *   request, FALSE otherwise.
   */
  public function applies(Request $request) {
    // If you return TRUE and the method Authentication logic fails,
    // you will get out from Drupal navigation if you are logged in.
    $method = [];

    // Check if the client uses the "Bearer" authentication scheme
    // to transmit the access token.
    // See https://tools.ietf.org/html/rfc6750#section-2.1
    if (stripos(trim($request->headers->get('authorization')), 'Bearer') !== FALSE) {
      $method[] = t('Authorization Request Header Field');
    }

    // Check if the access token is in the entity-body of the HTTP request,
    // and if the client adds the access token to the request-body using the
    // "access_token" parameter.
    // See https://tools.ietf.org/html/rfc6750#section-2.2
    if (trim($request->headers->get('content-type')) == 'application/x-www-form-urlencoded' &&
        empty($request->query->get('access_token')) &&
        trim($request->getMethod()) !== 'GET' &&
        stripos(trim($request->getContent()), 'access_token') !== FALSE) {
      $method[] = t('Form-Encoded Body Parameter');
    }

    // Check if the access token is in URI of the HTTP request,
    // the client adds the access token to the request URI query component
    // using the "access_token" parameter.
    // See https://tools.ietf.org/html/rfc6750#section-2.3
    if (!empty($request->get('access_token')) && stripos(trim($request->getContent()), 'access_token') === FALSE) {
      $method[] = t('URI Query Parameter');
    }
    // There are three methods of sending bearer access tokens in
    // resource requests to resource servers.
    // Clients MUST NOT use more than one method to transmit the token in each
    // request.
    if (!empty($method) && count($method) == 1) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate(Request $request) {

    try {
      if (!empty($request->headers->get('authorization'))) {
        $token = $this->getInfoToken($request->headers->get('authorization'), 'token');
      }
      if (!empty($request->get('access_token'))) {
        $token = $request->get('access_token');
      }
      // Determine if $token is empty.
      if (empty($token)) {
        throw new \InvalidArgumentException("The client has not transmitted the token in the request.");
      }
      // Retrieve access token data.
      $info = $this->storage->getAccessToken($token);
      if (empty($info)) {
        throw new \InvalidArgumentException("The token: " . $token . " provided is not registered.");
      }
      // Determine if $info['server'] is empty.
      if (empty($info['server'])) {
        throw new \Exception("OAuth2 server was not set");
      }
      // Set $oauth2_server_name.
      $oauth2_server_name = 'oauth2_server.server.' . $info['server'];
      // Retrieves the configuration object.
      $config = \Drupal::config($oauth2_server_name);
      // Determine if $config is empty.
      if (empty($config)) {
        throw new \Exception("The config for '.$oauth2_server_name.' server could not be loaded.");
      }
      $oauth2_server_settings = $config->get('settings');
      if (empty($oauth2_server_settings['advanced_settings']) || empty($oauth2_server_settings['advanced_settings']['access_lifetime'])) {
        throw new \Exception("The access_lifetime was not set.");
      }
      if (REQUEST_TIME > ($info['expires'] + $oauth2_server_settings['advanced_settings']['access_lifetime'])) {
        throw new \Exception("The token is expired.");
      }

      return $this->entityManager->getStorage('user')->load($info['user_id']);
    }
    catch (\Exception $e) {
      \Drupal::logger('access denied')->warning($e->getMessage());
      return $this->entityManager->getStorage('user')->load(0);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function cleanup(Request $request) {}

  /**
   * {@inheritdoc}
   */
  public function handleException(GetResponseForExceptionEvent $event) {
    $exception = $event->getException();
    if ($exception instanceof AccessDeniedHttpException) {
      $event->setException(new UnauthorizedHttpException('Invalid consumer origin.', $exception));
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Generates keys from "Authorization" request header field.
   *
   * @param string $authorization
   *   The "Authorization" request header field.
   * @param string $key
   *   Token / authentication_scheme.
   *
   * @return array|false
   *   An array with the following keys:
   *   - authentication_scheme: (string) HTTP Authentication Scheme.
   *   - token: (string) $token.
   */
  protected function getInfoToken($authorization = NULL, $key = NULL) {

    if (empty($authorization)) {
      return FALSE;
    }

    @list($authentication_scheme, $token) = explode(' ', $authorization, 2);
    if (empty($token)) {
      return FALSE;
    }
    $infoToken = [
      'authentication_scheme' => $authentication_scheme,
      'token' => $token,
    ];
    if (!empty($key) && array_key_exists($key, $infoToken)) {
      return $infoToken[$key];
    }
    else {
      return $infoToken;
    }
  }

}
