<?php

namespace Drupal\social_auth_esia\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\social_api\Plugin\NetworkManager;
use Drupal\social_auth\SocialAuthUserManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for Social Auth ESIA routes.
 */
class EsiaAuthController extends ControllerBase {

  use MessengerTrait;

  /**
   * The network plugin manager.
   *
   * @var \Drupal\social_api\Plugin\NetworkManager
   */
  protected $pluginNetworkManager;

  /**
   * The social auth user manager.
   *
   * @var \Drupal\social_auth\SocialAuthUserManager
   */
  protected $socialAuthUserManager;

  /**
   * The private temp store.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStorePrivate;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The controller constructor.
   *
   * @param \Drupal\social_api\Plugin\NetworkManager $plugin_network_manager
   *   The network plugin manager.
   * @param \Drupal\social_auth\SocialAuthUserManager $social_auth_user_manager
   *   The social auth user manager.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_private
   *   The private temp store.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   */
  public function __construct(NetworkManager $plugin_network_manager, SocialAuthUserManager $social_auth_user_manager, PrivateTempStoreFactory $temp_store_private, Request $request) {
    $this->pluginNetworkManager = $plugin_network_manager;
    $this->socialAuthUserManager = $social_auth_user_manager;
    $this->tempStorePrivate = $temp_store_private;
    $this->request = $request;

    // Sets the plugin id.
    $this->socialAuthUserManager->setPluginId('social_auth_esia');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.network.manager'),
      $container->get('social_auth.user_manager'),
      $container->get('tempstore.private'),
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * Builds the response.
   */
  public function redirectToEsia() {
    /* @var \Ekapusta\OAuth2Esia\Provider\EsiaProvider|false $provider */
    $provider = $this->pluginNetworkManager->createInstance('social_auth_esia')->getSdk();

    // If client could not be obtained.
    if (!$provider) {
      $this->messenger()->addError($this->t('Social Auth ESIA not configured properly. Contact site administrator.'));
      return $this->redirect('user.login');
    }

    $auth_url = $provider->getAuthorizationUrl();
    $private_collection = $this->tempStorePrivate->get('social_auth_esia');
    $private_collection->set('state', $provider->getState());

    return new TrustedRedirectResponse($auth_url);
  }

  /**
   * Builds the response.
   */
  public function redirectFromEsia() {

    if ($this->request->query->has('error')) {
      $this->messenger()->addError($this->request->query->get('error_description'));
      return $this->redirect('user.login');
    }

    $private_collection = $this->tempStorePrivate->get('social_auth_esia');
    $state_stored = $private_collection->get('state');

    if ($state_stored !== $this->request->query->get('state')) {
      $this->messenger()->addError('ESIA login is failed.');
      return $this->redirect('user.login');
    }

    $private_collection->set('code', $this->request->query->get('code'));

    /* @var \Ekapusta\OAuth2Esia\Provider\EsiaProvider|false $provider */
    $provider = $this->pluginNetworkManager->createInstance('social_auth_esia')->getSdk();
    $token = $provider->getAccessToken('authorization_code', [
      'code' => $private_collection->get('code'),
    ]);

    $owner_data = $provider->getResourceOwner($token);
    $owner_id = $owner_data->getId();

    $owner_data_array = $owner_data->toArray();

    $email_key = array_search('EML', array_column($owner_data_array['contacts']['elements'], 'type'));
    $email = $owner_data_array['contacts']['elements'][$email_key]['value'];

    // Format full name.
    if (isset($owner_data_array['firstName'])) {
      $name_parts = [];

      if (isset($owner_data_array['lastName'])) {
        $name_parts[] = $owner_data_array['lastName'];
      }

      $name_parts[] = $owner_data_array['lastName'];

      if (isset($owner_data_array['middleName'])) {
        $name_parts[] = $owner_data_array['middleName'];
      }

      $name = implode(' ', $name_parts);
    }
    else {
      $name = $email;
    }

    // If user information could be retrieved.
    return $this->socialAuthUserManager->authenticateUser($email, $name, $owner_id, $token);
  }

}
