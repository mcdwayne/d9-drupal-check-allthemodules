<?php

namespace Drupal\janrain_connect\Service;

use Drupal\Core\Url;
use Drupal\janrain_connect\Constants\JanrainConnectWebServiceConstants;
use Drupal\janrain_connect\Constants\JanrainConnectConstants;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Config\ConfigFactory;
use JanrainRest\JanrainRest as Janrain;
use Psr\Log\LoggerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * JanrainConnect Flow Class.
 */
class JanrainConnectConnector {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  public $config;

  /**
   * The Janrain connect validate service.
   *
   * @var \Drupal\janrain_connect\Service\JanrainConnectValidate
   */
  public $janrainConnectValidate;

  /**
   * The Janrain core library object.
   *
   * @var \JanrainRest\JanrainRest
   */
  public $janrainApi;

  /**
   * Module Handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * JanrainConnector constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   Config Factory.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   Language Manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\janrain_connect\Service\JanrainConnectValidate $janrain_connect_validate
   *   JanrainConnect validate.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    ConfigFactory $config_factory,
    LanguageManagerInterface $language_manager,
    LoggerInterface $logger,
    JanrainConnectValidate $janrain_connect_validate,
    ModuleHandlerInterface $module_handler
  ) {
    $this->configFactory = $config_factory;
    $this->config = $config_factory->get('janrain_connect.settings');
    $this->janrainConnectValidate = $janrain_connect_validate;
    $this->moduleHandler = $module_handler;

    // Figure out which language to use.
    // Default is en-US.
    $locale = $this->config->get('default_language');
    $lid = $language_manager->getCurrentLanguage()->getId();
    $language = $this->config->get('flow_language_mapping_' . $lid);
    if ($language) {
      $locale = $language;
    }

    $direct_client = $this->getDirectClient();

    // This object is the interface to perform calls to Janrain API.
    // Empty parameters are full_login credentials we will not use. They have
    // administrative rights and should be used with care.
    $this->janrainApi = new Janrain(
      $this->config->get('capture_server_url'),
      $this->config->get('config_server'),
      $this->config->get('flowjs_url'),
      empty($direct_client) ? '' : $direct_client['client_id'],
      empty($direct_client) ? '' : $direct_client['client_secret'],
      $this->config->get('client_id'),
      $this->config->get('client_secret'),
      $this->config->get('application_id'),
      $locale,
      $this->config->get('flow_name'),
      $logger
    );
  }

  /**
   * Get direct client by janrain connect super admin module.
   *
   * @return array
   *   Full client.
   */
  private function getDirectClient() {
    if (!$this->moduleHandler->moduleExists('janrain_connect_super_admin')) {
      return [];
    }

    // We can not use Depency Injection here because we need check if the
    // project is enabled. @codingStandardsIgnoreLine
    $direct_access_values = \Drupal::service('janrain_connect_super_admin.services')->getConfigDirectAccess();

    if (empty($direct_access_values['direct_access_id']) || empty($direct_access_values['direct_access_secret'])) {
      return [];
    }

    return [
      'client_id' => $direct_access_values['direct_access_id'],
      'client_secret' => $direct_access_values['direct_access_secret'],
    ];
  }

  /**
   * Register User on Janrain.
   *
   * @param array $data
   *   Array with Data.
   * @param string $redirect_uri
   *   Redirect URL.
   *
   * @return mixed
   *   Return janrain result using janrainCalls.
   */
  public function register(array $data, $redirect_uri = NULL) {
    if (!$redirect_uri) {
      $redirect_uri = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
    }

    $result = $this->janrainApi->registerNativeTraditional(
      $this->config->get('client_id'),
      $this->config->get('flow_version'),
      $redirect_uri,
      JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FORM_REGISTRATION,
      $data
    );

    $this->janrainConnectValidate->validateResponse($result);

    return $result;
  }

  /**
   * Social Registrate User on Janrain.
   *
   * @param string $engageToken
   *   Social engage token.
   * @param array $data
   *   Array with Data.
   * @param string $redirect_uri
   *   Redirect URL.
   *
   * @return mixed
   *   Return janrain result using janrainCalls.
   */
  public function socialRegister(string $engageToken, array $data, $redirect_uri = NULL) {
    if (!$redirect_uri) {
      $redirect_uri = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
    }

    $result = $this->janrainApi->registerNative(
      $this->config->get('client_id'),
      $this->config->get('flow_version'),
      $redirect_uri,
      JanrainConnectWebServiceConstants::JANRAIN_CONNECT_SOCIAL_FORM_SIGNIN,
      $engageToken,
      $data
    );

    $this->janrainConnectValidate->validateResponse($result);

    return $result;
  }

  /**
   * Get Settings on Janrain.
   */
  public function getJanrainSettings() {

    $client_id = $this->config->get('client_id');
    $client_secret = $this->config->get('client_secret');

    $result = $this->janrainApi->settingsItems($client_id, $client_secret);

    $this->janrainConnectValidate->validateResponse($result);

    return $result;
  }

  /**
   * Login User on Janrain.
   */
  public function login($data, $mergeToken = NULL) {

    if ($mergeToken) {
      $data['merge_token'] = $mergeToken;
    }

    $result = $this->janrainApi->authNativeTraditional(
      $this->config->get('client_id'),
      $this->config->get('flow_version'),
      $this->config->get('app_url'),
      JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FORM_SIGNIN,
      $data
    );

    $this->janrainConnectValidate->validateResponse($result);

    return $result;
  }

  /**
   * Login User on Janrain.
   *
   * @param string $engageToken
   *   Engage token.
   * @param string $mergeToken
   *   Engage merge token.
   * @param string $redirect
   *   Redirect Url.
   *
   * @return array
   *   Janrain api return.
   */
  public function socialLogin($engageToken, $mergeToken, $redirect) {
    $result = $this->janrainApi->authNative(
      $this->config->get('client_id'),
      $this->config->get('flow_version'),
      $redirect,
      JanrainConnectWebServiceConstants::JANRAIN_CONNECT_SOCIAL_FORM_SIGNIN,
      $engageToken,
      $mergeToken
    );

    $this->janrainConnectValidate->validateResponse($result);

    return $result;
  }

  /**
   * Get list of active social providers.
   *
   * @param string $provider
   *   Social media id.
   * @param string $redirect
   *   Redirect Url.
   * @param string $locale
   *   Language like en-us.
   *
   * @return string
   *   Given provider's login URL.
   */
  public function getSocialProviders($provider, $redirect, $locale) {
    return $this->janrainApi->socialLoginUrl($provider, $redirect, $locale);
  }

  /**
   * Find user data from Entity Api.
   */
  public function findUser($filter, $client_id = FALSE, $client_secret = FALSE) {

    $type_name = $this->config->get('entity_type');
    $result = $this->janrainApi->entityFind($filter, $type_name, NULL, $client_id, $client_secret);

    $this->janrainConnectValidate->validateResponse($result);

    return $result;
  }

  /**
   * Get user data from Entity Api.
   *
   * @param string $accessToken
   *   Access Token.
   */
  public function getUserData($accessToken) {
    $result = $this->janrainApi->entity($accessToken, 'user');

    $this->janrainConnectValidate->validateResponse($result);

    return $result;
  }

  /**
   * Janrain Forgot Password.
   *
   * @param string $email
   *   Email.
   * @param string $redirect_uri
   *   Redirect URI.
   */
  public function forgotPassword($email, $redirect_uri = NULL) {
    if (!$redirect_uri) {
      $redirect_uri = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
    }

    $data = [
      'signInEmailAddress' => $email,
    ];

    $result = $this->janrainApi->forgotPasswordNative(
      $this->config->get('client_id'),
      $this->config->get('flow_version'),
      $redirect_uri,
      JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FORM_FORGOT_PASSWORD,
      $data
    );

    $this->janrainConnectValidate->validateResponse($result);

    return $result;
  }

  /**
   * Get Verification Code.
   *
   * @param string $uuid
   *   UUID.
   */
  public function getVerificationCode($uuid) {
    $result = $this->janrainApi->getVerificationCode(
      $uuid,
      'user',
      'emailVerified'
    );

    $this->janrainConnectValidate->validateResponse($result);

    return $result;
  }

  /**
   * Generic Custom Call.
   *
   * @param array $data
   *   The data that will send to service.
   * @param string $service_url
   *   The endpoint in Janrain API.
   * @param string $form_name
   *   The name of the form in your flow that you will use.
   * @param string $redirect_uri
   *   Redirect URL.
   *
   * @return array
   *   Janrain response.
   */
  public function genericCustomCall(array $data, $service_url, $form_name, $redirect_uri = NULL) {

    if (!$redirect_uri) {
      $redirect_uri = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
    }

    $result = $this->janrainApi->gerenicCustomCall(
      $this->config->get('client_id'),
      $this->config->get('flow_version'),
      $redirect_uri,
      $form_name,
      $data,
      $service_url
    );

    $this->janrainConnectValidate->validateResponse($result);

    return $result;
  }

  /**
   * Resend Verification User.
   */
  public function resendVerificationUser($data, $redirect_uri = FALSE) {

    if (!$redirect_uri) {
      $redirect_uri = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
    }

    $result = $this->janrainApi->verifyEmailNative(
      $this->config->get('client_id'),
      $this->config->get('flow_version'),
      $redirect_uri,
      JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FORM_RESEND_VERIFICATION_EMAIL,
      $data
    );

    $this->janrainConnectValidate->validateResponse($result);

    return $result;
  }

  /**
   * Change Password.
   *
   * @param string $actual_password
   *   Actual Password.
   * @param string $new_password
   *   New Password.
   * @param string $new_password_confirm
   *   Confirmation for New Password.
   * @param string $token
   *   Access Token.
   */
  public function changePassword($actual_password, $new_password, $new_password_confirm, $token) {
    // If user is not logged by Janrain, there is no token.
    if (!$token) {
      return [
        'has_errors' => TRUE,
        JanrainConnectWebServiceConstants::JANRAIN_CONNECT_ERROR_DESCRIPTION => JanrainConnectConstants::JANRAIN_CONNECT_CHANGE_PASSWORD_GENERIC_ERROR,
      ];
    }

    $data = [
      'currentPassword' => $actual_password,
      'newPassword' => $new_password,
      'newPasswordConfirm' => $new_password_confirm,
    ];

    // Token will be NULL if user didn't signin via Janrain.
    $result = $this->janrainApi->updateProfileNative(
      $this->config->get('client_id'),
      $this->config->get('flow_version'),
      JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FORM_CHANGE_PASSWORD,
      $token,
      $data
    );

    $this->janrainConnectValidate->validateResponse($result);

    return $result;
  }

  /**
   * Entity Update.
   */
  public function entityUpdate($email, $value, $client_id = FALSE, $client_secret = FALSE) {

    if (empty($client_id)) {
      $client_id = $this->config->get('client_id');
    }

    if (empty($client_secret)) {
      $client_secret = $this->config->get('client_secret');
    }

    $type_name = $this->config->get('entity_type');

    $result = $this->janrainApi->entityUpdate(FALSE, FALSE, $type_name, $email, $value, $client_id, $client_secret);

    $this->janrainConnectValidate->validateResponse($result);

    return $result;
  }

  /**
   * Update a user profile in Janrain.
   *
   * @param string $access_token
   *   Access token provided when user is logged in.
   * @param array $data
   *   User data to be updated.
   * @param string $formName
   *   The name of the form in your flow.
   *
   * @return array
   *   Janrain response.
   */
  public function updateUserProfile($access_token, array $data, $formName = JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FORM_EDIT_PROFILE) {
    // If user is not logged by Janrain, there is no token.
    if (!$access_token) {
      return [
        'has_errors' => TRUE,
        JanrainConnectWebServiceConstants::JANRAIN_CONNECT_ERROR_DESCRIPTION => JanrainConnectConstants::JANRAIN_CONNECT_CHANGE_PASSWORD_GENERIC_ERROR,
      ];
    }

    $result = $this->janrainApi->updateProfileNative(
      $this->config->get('client_id'),
      $this->config->get('flow_version'),
      $formName,
      $access_token,
      $data
    );

    $this->janrainConnectValidate->validateResponse($result);

    return $result;
  }

  /**
   * Use Verification code.
   *
   * @param string $verificationCode
   *   The verification code to validate.
   *
   * @return array
   *   Janrain response.
   */
  public function useVerificationCode($verificationCode) {
    $result = $this->janrainApi->useVerificationCode($verificationCode);

    $this->janrainConnectValidate->validateResponse($result);

    return $result;
  }

  /**
   * Use code to get token.
   *
   * @param string $grantType
   *   Available values are authorization_code and refresh_token.
   * @param string $code
   *   The authorization code received after a user has successfully
   *   authenticated.
   * @param string $redirectUri
   *   The same value as the redirect_uri that API call requests.
   *
   * @return array
   *   Janrain response.
   */
  public function useCode($grantType, $code, $redirectUri = NULL) {
    if (!$redirectUri) {
      $redirectUri = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
    }

    $result = $this->janrainApi->token($grantType, $code, $redirectUri);

    $this->janrainConnectValidate->validateResponse($result);

    return $result;
  }

}
