<?php

namespace Drupal\janrain_connect_ui\EventSubscriber;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TempStore\TempStoreException;
use Drupal\user\UserDataInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Drupal\janrain_connect\Constants\JanrainConnectConstants;
use Drupal\janrain_connect\Constants\JanrainConnectWebServiceConstants;
use Drupal\janrain_connect\Service\JanrainConnectConnector;
use Drupal\janrain_connect\Service\JanrainConnectLogin;
use Drupal\janrain_connect_ui\Event\JanrainConnectUiEvents;
use Drupal\janrain_connect_ui\Event\JanrainConnectUiSubmitEvent;
use Drupal\janrain_connect_ui\Service\JanrainConnectUiUsers;
use Drupal\Core\Url;
use Symfony\Component\Yaml\Yaml;
use Drupal\janrain_connect_ui\Service\JanrainConnectUiTokenService;
use Drupal\janrain_connect_ui\Exception\AuthenticationVerifyAccountException;
use Drupal\janrain_connect_ui\Exception\AuthenticationDrupalLoginException;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\janrain_connect_ui\Exception\AuthenticationConstraintException;

/**
 * Subscribe to Janrain Connect Submit.
 */
class JanrainConnectUiSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The user data service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * JanrainConnectConnector.
   *
   * @var \Drupal\janrain_connect\Service\JanrainConnectConnector
   */
  private $janrainConnector;

  /**
   * JanrainConnectUsers.
   *
   * @var \Drupal\janrain_connect_ui\Service\JanrainConnectUiUsers
   */
  private $janrainUsers;

  /**
   * Module Handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private $moduleHandler;

  /**
   * Symfony session handler.
   *
   * @var \Symfony\Component\HttpFoundation\Session\Session
   */
  private $session;

  /**
   * JanrainConnectLogin.
   *
   * @var \Drupal\janrain_connect\Service\JanrainConnectLogin
   */
  private $janrainLogin;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * JanrainConnectToken.
   *
   * @var \Drupal\janrain_connect_ui\Service\JanrainConnectUiTokenService
   */
  protected $janrainConnectUiTokenService;

  /**
   * The tempstore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The login constraint plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $loginConstraintManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    JanrainConnectConnector $janrain_connector,
    Session $session,
    ModuleHandlerInterface $module_handler,
    JanrainConnectUiUsers $janrain_users,
    RequestStack $request_stack,
    UserDataInterface $user_data,
    JanrainConnectLogin $janrain_login,
    ConfigFactory $config_factory,
    JanrainConnectUiTokenService $janrain_connect_ui_token_service,
    PrivateTempStoreFactory $temp_store_factory,
    PluginManagerInterface $login_constraint_manager
  ) {
    $this->janrainConnector = $janrain_connector;
    $this->session = $session;
    $this->moduleHandler = $module_handler;
    $this->janrainUsers = $janrain_users;
    $this->requestStack = $request_stack;
    $this->userData = $user_data;
    $this->janrainLogin = $janrain_login;
    $this->config = $config_factory->get('janrain_connect.settings');
    $this->janrainConnectUiTokenService = $janrain_connect_ui_token_service;
    $this->tempStoreFactory = $temp_store_factory;
    $this->loginConstraintManager = $login_constraint_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {

    $events[JanrainConnectUiEvents::EVENT_SUBMIT][] = ['submitRequestToJanrain'];

    return $events;
  }

  /**
   * Submit Registration.
   */
  public function submitRequestToJanrain(JanrainConnectUiSubmitEvent $event) {
    $data = $event->getData();
    $form = $event->getForm();
    $form_state = $event->getFormState();

    switch ($event->getFormId()) {
      case JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FORM_REGISTRATION:
        $this->sendRegistrationToJanrain($data, $form, $form_state);
        break;

      case JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FORM_SIGNIN:
        $this->sendLoginToJanrain($data, $form, $form_state);
        break;

      case JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FORM_EDIT_PROFILE:
        $this->sendProfileUpdateToJanrain($data, $form, $form_state);
        break;

      case JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FORM_FORGOT_PASSWORD:
        $this->sendForgotPasswordToJanrain($data, $form, $form_state);
        break;

      case JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FORM_CHANGE_PASSWORD:
        $this->sendChangePasswordToJanrain($data, $form, $form_state);
        break;

      case JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FORM_RESEND_VERIFICATION_EMAIL:
        $this->sendVerificationUserToJanrain($data, $form, $form_state);
        break;

      case JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FORM_CHANGE_PASSWORD_NO_AUTH:
        $this->sendChangePasswordNoAuthToJanrain($data, $form, $form_state);
        break;
    }
  }

  /**
   * Send registration data to Janrain.
   *
   * @param array $data
   *   User data to create a janrain registration.
   * @param mixed $form
   *   Form.
   * @param mixed $form_state
   *   Form State.
   */
  private function sendRegistrationToJanrain(array $data, $form, $form_state) {
    // Try register in Janrain.
    $result = $this->janrainConnector->register($data);

    if (!empty($result['has_errors'])) {
      $this->printErrorMessages(
        $result,
        $form_state,
        JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FORM_REGISTRATION
      );
      return;
    }

    // Persist user in Drupal site.
    $status = $this->janrainUsers->persistUser($data, $result, FALSE);

    if ($status !== SAVED_NEW) {
      // All messages from Janrain must have translation. @codingStandardsIgnoreLine
      $form_state->setErrorByName(JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FORM_REGISTRATION, $this->t(JanrainConnectConstants::JANRAIN_CONNECT_REGISTRATION_GENERIC_ERROR));
      return;
    }

    try {
      $user = user_load_by_name($result['capture_user']->uuid);
      $this->verifyConstraintsAndDrupalLogin($result, $user);
      $this->printSuccessMessage($form_state, JanrainConnectConstants::JANRAIN_CONNECT_REGISTRATION_MESSAGE_SUCCESS);
      $success_message = '<script>jQuery(window).bind("dialog:beforeclose", function(context) {location.reload();});</script>';
      drupal_set_message($success_message, 'status');

      $this->successRedirect($form_state);
    }
    catch (AuthenticationVerifyAccountException $e) {
      // All messages from Janrain must have translation. @codingStandardsIgnoreLine
      $verify_email_message = $error = $this->t(JanrainConnectConstants::JANRAIN_CONNECT_AUTHENTICATION_VERIFY_ACCOUNT_ERROR);
      $form_id = $form_state->get('form_id');
      $ajax_settings = $this->getJanrainConnectUiAjaxSettings($form_id);
      if (!empty($ajax_settings['verify_email_message'])) {
        $tokens = $this->janrainConnectUiTokenService->getMessageFormTokens($ajax_settings['verify_email_message'], $form_state);
        // All messages from Janrain must have translation. @codingStandardsIgnoreLine
        $verify_email_message = $this->t($ajax_settings['verify_email_message'], $tokens);
      }
      drupal_set_message($verify_email_message, 'status');
    }
    catch (AuthenticationConstraintException $e) {
      drupal_set_message($e->getMessage(), 'status');
    }
    catch (AuthenticationDrupalLoginException $e) {
      $form_state->setErrorByName(
        JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FORM_REGISTRATION,
        // All messages from Janrain must have translation. @codingStandardsIgnoreLine
        $this->t(JanrainConnectConstants::JANRAIN_CONNECT_AUTHENTICATION_GENERIC_ERROR)
      );
    }
  }

  /**
   * Send login data to Janrain.
   *
   * @param array $data
   *   Required data to allow user login.
   * @param mixed $form
   *   Form.
   * @param mixed $form_state
   *   Form State.
   */
  private function sendLoginToJanrain(array $data, $form, $form_state) {
    // Try login in Janrain.
    $result = $this->janrainConnector->login($data);

    if (!empty($result['has_errors'])) {
      $this->printErrorMessages(
        $result,
        $form_state,
        JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FORM_SIGNIN
      );
      return;
    }

    // Load by e-mail because we don't have the UUID.
    $user = user_load_by_mail($data['signInEmailAddress']);

    // If user exists on janrain but not exists in Drupal, create user.
    if (empty($user)) {
      $data_register['emailAddress'] = $data['signInEmailAddress'];
      $user = $this->janrainUsers->persistUser($data_register, $result, TRUE);
    }

    if (empty($user)) {
      $form_state->setErrorByName(
        JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FORM_SIGNIN,
        // All messages from Janrain must have translation. @codingStandardsIgnoreLine
        $this->t(JanrainConnectConstants::JANRAIN_CONNECT_AUTHENTICATION_GENERIC_ERROR)
      );
      return;
    }

    $error = NULL;
    try {
      $this->verifyConstraintsAndDrupalLogin($result, $user);

      $this->printSuccessMessage(
        $form_state,
        JanrainConnectConstants::JANRAIN_CONNECT_LOGIN_MESSAGE_SUCCESS
      );
    }
    catch (AuthenticationVerifyAccountException $e) {
      $error = $this->t(
        // All messages from Janrain must have translation. @codingStandardsIgnoreLine
        JanrainConnectConstants::JANRAIN_CONNECT_AUTHENTICATION_VERIFY_ACCOUNT_ERROR
      );
    }
    catch (AuthenticationConstraintException $e) {
      $error = $e->getMessage();
    }
    catch (AuthenticationDrupalLoginException $e) {
      $error = $this->t(
        // All messages from Janrain must have translation. @codingStandardsIgnoreLine
        JanrainConnectConstants::JANRAIN_CONNECT_AUTHENTICATION_GENERIC_ERROR
      );
    }

    if (!empty($error)) {
      $form_state->setErrorByName(
        JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FORM_SIGNIN,
        $error
      );
      return;
    }

    $this->successRedirect($form_state);
  }

  /**
   * Verify form is using ajax.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form State.
   *
   * @return bool
   *   Form is using ajax.
   */
  private function formIsUsingAjax(FormStateInterface $form_state) {
    $ajax_settings = $this
      ->getJanrainConnectUiAjaxSettings($form_state->get('form_id'));

    return !empty($ajax_settings['use_ajax']);
  }

  /**
   * Verify constraints and Drupal login.
   *
   * @param array $result
   *   Result returned by Janrain API.
   * @param object $user
   *   Drupal user.
   *
   * @throws \Drupal\janrain_connect_ui\Exception\AuthenticationVerifyAccountException
   * @throws \Drupal\janrain_connect_ui\Exception\AuthenticationDrupalLoginException
   * @throws \Drupal\janrain_connect_ui\Exception\AuthenticationConstraintException
   */
  private function verifyConstraintsAndDrupalLogin(array $result, $user) {
    if (!$this->emailVerified($result['capture_user'])) {
      throw new AuthenticationVerifyAccountException();
    }

    foreach ($this->loginConstraintManager->getDefinitions() as $id => $definition) {
      try {
        $plugin_object = $this->loginConstraintManager->createInstance($id);
      }
      catch (PluginException $e) {
        throw new AuthenticationDrupalLoginException();
      }
      if (!$plugin_object->validate($result)) {
        throw new AuthenticationConstraintException(
          $plugin_object->getErrorMessage()
        );
      }
    }

    if (!$this->janrainLogin->login($result['access_token'], $user)) {
      throw new AuthenticationDrupalLoginException();
    }
  }

  /**
   * Check if email verified.
   *
   * @param object $capture_user
   *   User returned by Janrain API.
   *
   * @return bool
   *   If email verified is disable is TRUE or email was verified in Janrain.
   */
  private function emailVerified($capture_user) {
    $check_email_verified = $this->config->get('config_auth_check_email_verified');
    return empty($check_email_verified) || !empty($capture_user->emailVerified);
  }

  /**
   * Get Janrain Ui Ajax Settings.
   *
   * @param string $form_id
   *   Form ID.
   *
   * @return mixed
   *   Return value.
   */
  private function getJanrainConnectUiAjaxSettings(string $form_id) {
    $configuration_forms = $this->config->get('configuration_forms');
    $ajax_settings = Yaml::parse($configuration_forms);

    if (!empty($ajax_settings[$form_id])) {
      return $ajax_settings[$form_id];
    }

    return '';
  }

  /**
   * Send profile update to Janrain.
   *
   * @param array $data
   *   User data to update a profile in Janrain.
   * @param mixed $form
   *   Form.
   * @param mixed $form_state
   *   Form State.
   */
  private function sendProfileUpdateToJanrain(array $data, $form, $form_state) {
    $accessToken = $this->session->get('janrain_connect_access_token');

    // TODO: Remove this condition after the whole login process is done.
    if (empty($accessToken)) {
      // @codingStandardsIgnoreLine.
      drupal_set_message('You are not logged in', 'warning');
      return;
    }

    $result = $this->janrainConnector->updateUserProfile($accessToken, $data);

    if (!empty($result['has_errors'])) {
      $this->printErrorMessages(
        $result,
        $form_state,
        JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FORM_EDIT_PROFILE
      );
      return;
    }

    $this->printSuccessMessage($form_state, JanrainConnectConstants::JANRAIN_CONNECT_PROFILE_UPDATE_MESSAGE_SUCCESS);

    $this->successRedirect($form_state);
  }

  /**
   * Send Forgot Password to Janrain.
   *
   * @param array $data
   *   User data to create a janrain registration.
   */
  // @codingStandardsIgnoreLine
  private function sendForgotPasswordToJanrain(array $data, $form, $form_state) {

    $result = $this->janrainConnector->forgotPassword($data['signInEmailAddress']);

    if (!empty($result['has_errors'])) {
      $this->printErrorMessages(
        $result,
        $form_state,
        JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FORM_FORGOT_PASSWORD
      );

      return FALSE;

    }

    $this->printSuccessMessage($form_state, JanrainConnectConstants::JANRAIN_CONNECT_FORGOT_PASSWORD_MESSAGE_SUCCESS);

    $this->successRedirect($form_state);
  }

  /**
   * Send Verification User to Janrain.
   *
   * @param array $data
   *   User data to change password.
   * @param mixed $form
   *   Form.
   * @param mixed $form_state
   *   Form State.
   */
  private function sendVerificationUserToJanrain(array $data, $form, $form_state) {
    // Call janrain service.
    $result = $this->janrainConnector->resendVerificationUser($data);

    if (!empty($result['has_errors'])) {
      $this->printErrorMessages(
        $result,
        $form_state,
        JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FORM_RESEND_VERIFICATION_EMAIL
      );
      return;
    }

    $this->printSuccessMessage(
      $form_state,
      JanrainConnectConstants::JANRAIN_CONNECT_RESEND_VERIFICATION_USER_SUCCESS
    );

    $this->successRedirect($form_state);
  }

  /**
   * Send Change Password to Janrain.
   *
   * @param array $data
   *   User data to change password.
   * @param mixed $form
   *   Form.
   * @param mixed $form_state
   *   Form State.
   */
  private function sendChangePasswordToJanrain(array $data, $form, $form_state) {

    $actual_password = $data['currentPassword'];
    $new_password = $data['newPassword'];
    $new_password_confirm = $data['newPasswordConfirm'];
    $token = $this->session->get('janrain_connect_access_token');

    $result = $this->janrainConnector->changePassword($actual_password, $new_password, $new_password_confirm, $token);

    if (!empty($result['has_errors'])) {
      $this->printErrorMessages(
        $result,
        $form_state,
        JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FORM_CHANGE_PASSWORD
      );

      return FALSE;

    }

    $this->printSuccessMessage($form_state, JanrainConnectConstants::JANRAIN_CONNECT_CHANGE_PASSWORD_MESSAGE_SUCCESS);

    $this->successRedirect($form_state);
  }

  /**
   * Send Change Password to Janrain.
   *
   * @param array $data
   *   User data to change password.
   * @param mixed $form
   *   Form.
   * @param mixed $form_state
   *   Form State.
   */
  private function sendChangePasswordNoAuthToJanrain(array $data, $form, $form_state) {
    $store = $this->tempStoreFactory->get('janrain_connect_ui_forgot_password_redirect_success');
    $result = $store->get('result');
    $accessToken = !empty($result['access_token']) ? $result['access_token'] : NULL;

    $result = $this->janrainConnector->updateUserProfile($accessToken, $data, JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FORM_CHANGE_PASSWORD_NO_AUTH);

    if (!empty($result['has_errors'])) {
      $this->printErrorMessages(
        $result,
        $form_state,
        JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FORM_CHANGE_PASSWORD_NO_AUTH
      );

      return;
    }

    try {
      $store->delete('result');
    }
    catch (TempStoreException $e) {
    }

    $this->printSuccessMessage($form_state, JanrainConnectConstants::JANRAIN_CONNECT_CHANGE_PASSWORD_NO_AUTH_MESSAGE_SUCCESS);

    $this->successRedirect($form_state);
  }

  /**
   * Print error messages.
   *
   * @param array $result
   *   Result returned by Janrain API.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form State.
   * @param string $form_id
   *   The form id.
   */
  private function printErrorMessages(array $result, FormStateInterface $form_state, $form_id) {

    // If exists janrain_connect validate, use it to get messages.
    if ($this->moduleHandler->moduleExists('janrain_connect_validate')) {

      // Use direct access because is possible uninstall the Janrain Connect
      // Validate. @codingStandardsIgnoreLine
      $messages = \Drupal::service('janrain_connect_validate.messages_mapping')->getMessagesFields($result, JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FORM_REGISTRATION);
    }
    else {
      $messages[$form_id] = $this->getGenericErrorMessage($form_id);
    }

    $configuration_form_field = [];

    if (!empty($form_id)) {

      $configuration_fields = $this->config->get('configuration_fields');

      $configuration_fields = Yaml::parse($configuration_fields);

      if (!empty($configuration_fields[$form_id])) {
        $configuration_form_field = $configuration_fields[$form_id];
      }

    }

    foreach ($messages as $field_id => $field_messages) {
      if (!is_array($field_messages)) {
        // All messages from Janrain must have translation. @codingStandardsIgnoreLine.
        $form_state->setErrorByName('', $this->t($field_messages));
        continue;
      }
      foreach ($field_messages as $message) {
        if (!empty($configuration_form_field[$field_id]['validation-update-message'][$message])) {
          $message = $configuration_form_field[$field_id]['validation-update-message'][$message];
        }

        if (!empty($field_id) && !empty($form_id) && $field_id == $form_id) {

          $configuration_forms = $this->config->get('configuration_forms');
          $configuration_forms = Yaml::parse($configuration_forms);

          if (!empty($configuration_forms[$form_id])) {
            $configuration_form = $configuration_forms[$form_id];
            if (!empty($configuration_form['default_field_error'])) {
              $field_id = $configuration_form['default_field_error'];
            }
          }
        }

        // @codingStandardsIgnoreLine.
        $form_state->setErrorByName($field_id, $this->t($message));
      }
    }

  }

  /**
   * Get generic error message by form id.
   *
   * @param string $form_id
   *   Form id.
   *
   * @return string
   *   Generic error message.
   */
  private function getGenericErrorMessage($form_id) {
    $generic_error_messages = [
      JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FORM_REGISTRATION => JanrainConnectConstants::JANRAIN_CONNECT_REGISTRATION_GENERIC_ERROR,
      JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FORM_SIGNIN => JanrainConnectConstants::JANRAIN_CONNECT_AUTHENTICATION_GENERIC_ERROR,
      JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FORM_EDIT_PROFILE => JanrainConnectConstants::JANRAIN_CONNECT_EDIT_PROFILE_GENERIC_ERROR,
      JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FORM_FORGOT_PASSWORD => JanrainConnectConstants::JANRAIN_CONNECT_FORGOT_PASSWORD_GENERIC_ERROR,
      JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FORM_CHANGE_PASSWORD => JanrainConnectConstants::JANRAIN_CONNECT_CHANGE_PASSWORD_GENERIC_ERROR,
      JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FORM_CHANGE_PASSWORD_NO_AUTH => JanrainConnectConstants::JANRAIN_CONNECT_CHANGE_PASSWORD_NO_AUTH_GENERIC_ERROR,
    ];

    return (isset($generic_error_messages[$form_id]) ?
      $generic_error_messages[$form_id] : '');
  }

  /**
   * Print success message.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form State.
   * @param string $default_message
   *   The default message.
   */
  private function printSuccessMessage(FormStateInterface $form_state, $default_message) {
    $form_id = $form_state->get('form_id');
    $ajax_settings = $this->getJanrainConnectUiAjaxSettings($form_id);

    if (isset($ajax_settings['success_message']) &&
      $ajax_settings['success_message'] === FALSE) {
      return;
    }

    // All messages from Janrain must have translation. @codingStandardsIgnoreLine
    $success_message = $this->t($default_message);
    if (!empty($ajax_settings['success_message'])) {
      $tokens = $this->janrainConnectUiTokenService->getMessageFormTokens($ajax_settings['success_message'], $form_state);
      // All messages from Janrain must have translation. @codingStandardsIgnoreLine
      $success_message = $this->t($ajax_settings['success_message'], $tokens);
    }

    // Message success should be translatable @codingStandardsIgnoreLine.
    drupal_set_message($success_message, 'status');
  }

  /**
   * Redirect success if form isn't ajax.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form State.
   */
  private function successRedirect(FormStateInterface $form_state) {
    // If form is using ajax, the success behavior is done by the function
    // getAjaxSuccessCommand in JanrainConnectUiForm.php.
    if ($this->formIsUsingAjax($form_state)) {
      return;
    }

    $form_id = $form_state->get('form_id');
    $form_settings = $this->getJanrainConnectUiAjaxSettings($form_id);

    // If success url is not set it will refresh the current page.
    if (empty($form_settings['success_url'])) {
      return;
    }

    // Save form state data in PrivateTempStore if enable.
    $store = $this->tempStoreFactory->get('janrain_connect_ui_redirect_success');
    try {
      if (!empty($form_settings['send_form_state_to_success_url'])) {
        $store->set('form_state', $form_state);
      }
      else {
        $store->delete('form_state');
      }
    }
    catch (TempStoreException $e) {
    }

    $url = Url::fromUserInput($form_settings['success_url']);
    $form_state->setRedirectUrl($url);
  }

}
