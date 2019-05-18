<?php

namespace Drupal\fitbit\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\fitbit\FitbitAccessTokenManager;
use Drupal\fitbit\FitbitClient;
use Drupal\user\PrivateTempStoreFactory;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Authorization extends ControllerBase {

  /**
   * Fitbit client.
   *
   * @var \Drupal\fitbit\FitbitClient
   */
  protected $fitbitClient;

  /**
   * Fitbit Access Token Manager.
   *
   * @var \Drupal\fitbit\FitbitAccessTokenManager
   */
  protected $fitbitAccessTokenManager;

  /**
   * Session storage.
   *
   * @var \Drupal\user\PrivateTempStore
   */
  protected $tempStore;

  /**
   * Current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Authorization constructor.
   *
   * @param FitbitClient $fitbit_client
   * @param FitbitAccessTokenManager $fitbit_access_token_manager
   * @param PrivateTempStoreFactory $private_temp_store_factory
   * @param Request $request
   * @param AccountInterface $current_user
   */
  public function __construct(FitbitClient $fitbit_client, FitbitAccessTokenManager $fitbit_access_token_manager, PrivateTempStoreFactory $private_temp_store_factory, Request $request, AccountInterface $current_user) {
    $this->fitbitClient = $fitbit_client;
    $this->fitbitAccessTokenManager = $fitbit_access_token_manager;
    $this->tempStore = $private_temp_store_factory->get('fitbit');
    $this->request = $request;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('fitbit.client'),
      $container->get('fitbit.access_token_manager'),
      $container->get('user.private_tempstore'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('current_user')
    );
  }

  /**
   * Receive the authorization code from a Fitibit Authorization Code Flow
   * redirect, and request an access token from Fitbit.
   */
  public function authorize() {

    try {
      // Try to get an access token using the authorization code grant.
      $access_token = $this->fitbitClient->getAccessToken('authorization_code', [
        'code' => $this->request->get('code')]
      );

      // Save access token details.
      $this->fitbitAccessTokenManager->save($this->currentUser->id(), [
        'access_token' => $access_token->getToken(),
        'expires' => $access_token->getExpires(),
        'refresh_token' => $access_token->getRefreshToken(),
        'user_id' => $access_token->getResourceOwnerId(),
      ]);

      drupal_set_message('You\'re Fitbit account is now connected.');

      return new RedirectResponse(Url::fromRoute('fitbit.user_settings', ['user' => $this->currentUser->id()])->toString());
    }
    catch (IdentityProviderException $e) {
      watchdog_exception('fitbit', $e);
    }
  }

  /**
   * Check the state key from Fitbit to protect against CSRF.
   */
  public function checkAccess() {
    return AccessResult::allowedIf($this->tempStore->get('state') == $this->request->get('state'));
  }
}
