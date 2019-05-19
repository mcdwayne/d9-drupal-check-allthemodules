<?php

namespace Drupal\smartid_auth\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Url;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GenericProvider;
use Drupal\user\Entity\User;

/**
 * Class SmartidController.
 *
 * @package Drupal\smartid_auth\Controller
 */
class SmartidController extends ControllerBase {

  /**
   * Path validator service.
   *
   * @var \Drupal\Core\Path\PathValidator
   */
  protected $pathValidator;

  /**
   * Logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * KillSwitch service.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  protected $killSwitch;

  /**
   * TempStoreFactory service.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * PrivateTempStore object.
   *
   * @var \Drupal\user\PrivateTempStore
   */
  protected $store;

  /**
   * EEid user id number.
   *
   * @var
   */
  protected $resourceOwner;

  /**
   * Is new account.
   *
   * @var
   */
  protected $isNew;

  /**
   * SmartidController constructor.
   *
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   Path validator service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   Logger service.
   * @param \Drupal\Core\PageCache\ResponsePolicy\KillSwitch $kill_switch
   *   KillSwitch service.
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   Private temp store factory object.
   */
  public function __construct(
    PathValidatorInterface $path_validator,
    LoggerChannelFactoryInterface $logger,
    KillSwitch $kill_switch,
    PrivateTempStoreFactory $temp_store_factory) {
    $this->pathValidator = $path_validator;
    $this->logger = $logger->get('smartid_auth');
    $this->killSwitch = $kill_switch;
    $this->tempStoreFactory = $temp_store_factory;
    $this->store = $this->tempStoreFactory->get('smartid_auth.smart_id');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('path.validator'),
      $container->get('logger.factory'),
      $container->get('page_cache_kill_switch'),
      $container->get('user.private_tempstore')
    );
  }

  /**
   * The main login page.
   *
   * @return \Symfony\Component\HttpFoundation\Response|array
   *   Redirect to user page if already logged in or
   *   authentication form render array.
   */
  public function login() {
    $user = $this->currentUser();

    if ($user->isAuthenticated()) {
      // Redirect user to user.page.
      return $this->redirect('user.page');
    }

    $smartid_form = $this->formBuilder()->getForm('Drupal\smartid_auth\Form\SmartidLoginForm');

    return [
      '#theme' => 'smartid_auth_login_content',
      '#forms' => [
        'smartid_auth_form' => $smartid_form,
      ],
    ];
  }

  /**
   * ID-Card authentication.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect user to page depending on authentication result.
   */
  public function smartidOauth() {
    $this->killSwitch->trigger();

    if (!$this->isSuccessful()) {
      drupal_set_message($this->t('Authentication failed'), 'error');
      return $this->redirect('smartid_auth.smartid_controller_login');
    }
    else {
      $this->loadUser();
      return new RedirectResponse($this->getLoginRedirect()->toString());
    }
  }

  /**
   * Determine login redirect.
   *
   * @return \Drupal\Core\Url
   *   Url object.
   */
  private function getLoginRedirect() {

    $config = $this->config('smartid_auth.settings');
    $redirect_path = $config->get('login_redirect');

    // If this account is new, you have to edit the password.
    $route_name = 'entity.user.edit_form';
    $route_parameters = [
      'user' => \Drupal::currentUser()->id(),
    ];

    $account = \Drupal::currentUser();
    if ($this->isNew) {
      $url = Url::fromRoute($route_name, $route_parameters);
      return $url;
    }

    if (empty($redirect_path)) {
      $url = Url::fromRoute('<front>');
    }
    else {
      $url = $this->pathValidator->getUrlIfValidWithoutAccessCheck($redirect_path);

      if (!$url) {
        $url = Url::fromRoute('<front>');
      }
    }

    return $url;
  }

  /**
   * Set oauth response code if successful.
   */
  public function isSuccessful() {

    $config = $this->config('smartid_auth.settings');
    $clientId = $config->get('client_id');
    $clientSecret = $config->get('client_secret');
    $redirectUri = Url::fromRoute("smartid_auth.oauth")->setAbsolute()->toString();

    $provider = new GenericProvider([
      'clientId'                => $clientId,
      'clientSecret'            => $clientSecret,
      'redirectUri'             => $redirectUri,
      'urlAuthorize'            => 'https://id.smartid.ee/oauth/authorize',
      'urlAccessToken'          => 'https://id.smartid.ee/oauth/access_token',
      'urlResourceOwnerDetails' => 'https://id.smartid.ee/api/v2/user_data',
    ]);

    try {

      // Try to get an access token using the authorization code grant.
      $accessToken = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code'],
      ]);

      $this->resourceOwner = $provider->getResourceOwner($accessToken)->toArray();

      return TRUE;

    }
    catch (IdentityProviderException $e) {

      // Failed to get the access token or user details.
      exit($e->getMessage());

    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function registerUser() {
    // Get register settings.
    $register = $this->config('user.settings')->get('register');

    switch ($register) {
      case USER_REGISTER_ADMINISTRATORS_ONLY:
        drupal_set_message($this->t("Your site administrator forbids user registration"), 'warning');
        return FALSE;

      break;

      case USER_REGISTER_VISITORS:
      case USER_REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL:
      default:
        drupal_set_message($this->t("Authentication succeeded, but no account was found, we have setup a new account for you"), 'warning');
        break;
    }

    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $user = User::create();

    $userName = strtolower($this->resourceOwner['firstname']);
    $pass = $userName;
    $user->setPassword($pass);
    $user->enforceIsNew();
    $user->setEmail('name@example.com');
    $user->setUsername($userName);

    $user->set('init', 'email');
    $user->set('langcode', $language);
    $user->set('preferred_langcode', $language);
    $user->set('preferred_admin_langcode', $language);
    $user->set('field_personal_id_code', $this->resourceOwner['idcode']);

    switch ($register) {
      case USER_REGISTER_VISITORS:
        $user->activate();
        drupal_set_message($this->t("Please update your email and password, 
      your initial username & password are: @pass", ["@pass" => $pass]), 'warning');
        break;

      case USER_REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL:
      default:
        drupal_set_message($this->t("Your account will be activated by site administrator"), 'warning');
        break;
    }

    // Save user account.
    $user->save();

    // New account.
    $this->isNew = TRUE;

    user_login_finalize($user);

  }

  /**
   * {@inheritdoc}
   */
  public function loadUser() {

    $user = self::findUserByPersonalIdCode($this->resourceOwner['idcode']);

    if ($user) {
      if ($user->isActive()) {
        user_login_finalize($user);
      }
      else {
        drupal_set_message($this->t("Your account is still blocked, it will be activated by site administrator"), "warning");
        return FALSE;
      }
    }
    else {
      // No user found found.
      self::registerUser();
    }
  }

  /**
   * Find user by personal id code.
   *
   * @param string $personal_id_code
   *   User personal ID code.
   *
   * @return \Drupal\user\UserInterface|null
   *   User entity or null when not found.
   */
  public function findUserByPersonalIdCode($personal_id_code) {
    $query = \Drupal::entityQuery('user');
    $query->condition('field_personal_id_code', $personal_id_code);
    $uid = current($query->execute());
    if ($uid) {
      return User::load($uid);
    }
    return FALSE;
  }

  /**
   * Smart ID response returns Personal ID code in PNO{country code}-XXXXXXXXXXX format.
   * Extract only ID numbers from code.
   *
   * @param $personal_id_code
   *
   * @return bool|string|null
   */
  public function smartIdextractUserPersonalIdCode($personal_id_code) {

    $pos = strpos($personal_id_code, '-');

    $extracted_id = NULL;
    if ($pos !== FALSE) {

      $country_code = substr($personal_id_code, 3, 2);
      if ($country_code !== 'EE') {

        exit('Wrong country!');
      }

      $extracted_id = substr($personal_id_code, $pos + 1);
    }

    return $extracted_id;
  }

}
