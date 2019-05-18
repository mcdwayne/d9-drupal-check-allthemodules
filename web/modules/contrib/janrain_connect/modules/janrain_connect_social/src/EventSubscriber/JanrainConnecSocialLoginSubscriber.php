<?php

namespace Drupal\janrain_connect_social\EventSubscriber;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Url;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Drupal\janrain_connect\Constants\JanrainConnectWebServiceConstants;
use Drupal\janrain_connect\Constants\JanrainConnectConstants;
use Drupal\janrain_connect\Service\JanrainConnectLogin;
use Drupal\janrain_connect\Service\JanrainConnectConnector;
use Drupal\janrain_connect_ui\Event\JanrainConnectUiAlterEvent;
use Drupal\janrain_connect_ui\Event\JanrainConnectUiEvents;
use Drupal\janrain_connect_ui\Event\JanrainConnectUiSubmitEvent;
use Drupal\janrain_connect_ui\Service\JanrainConnectUiUsers;
use Drupal\janrain_connect_social\Constants\JanrainConnectSocialConstants;

/**
 * Subscribe to Janrain Connect Submit.
 */
class JanrainConnecSocialLoginSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

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
   * User data.
   *
   * @var array
   */
  private $socialData;

  /**
   * If social account must be merged.
   *
   * @var bool
   */
  private $isMerge;

  /**
   * Social media name.
   *
   * @var bool
   */
  private $providerName;

  /**
   * JanrainConnectLogin.
   *
   * @var \Drupal\janrain_connect\Service\JanrainConnectLogin
   */
  private $janrainLogin;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    JanrainConnectConnector $janrain_connector,
    Session $session,
    ModuleHandlerInterface $module_handler,
    JanrainConnectUiUsers $janrain_users,
    RequestStack $request_stack,
    JanrainConnectLogin $janrain_login
  ) {
    $this->janrainConnector = $janrain_connector;
    $this->session = $session;
    $this->moduleHandler = $module_handler;
    $this->janrainUsers = $janrain_users;
    $this->requestStack = $request_stack;
    $this->janrainLogin = $janrain_login;
    $this->isMerge = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {

    $events[JanrainConnectUiEvents::EVENT_ALTER][] = ['handleSocialLogin'];
    $events[JanrainConnectUiEvents::EVENT_SUBMIT][] = ['submitRequestToJanrain'];

    return $events;
  }

  /**
   * Handles social login.
   */
  public function handleSocialLogin(JanrainConnectUiAlterEvent $event) {
    // We only handle social registration forms.
    if ($event->getFormId() != JanrainConnectWebServiceConstants::JANRAIN_CONNECT_SOCIAL_FORM_SIGNIN) {
      return;
    }

    $engage_token = $event->getData();
    $engage_token = $engage_token['token'];
    // If we are not accessing this page by a redirect from Janrain, we
    // won't have a token. In this case we don't populate the form fields.
    if (!$engage_token) {
      return;
    }

    $form = $event->getForm();

    if ($this->hasAccount($engage_token)) {

      // @Todo: Improve user Journey.
    // @codingStandardsIgnoreStart
    /**
     * // User is already registered. We just need to sign the user in.
     * $logged = $this->janrainLogin->mergeLogin($this->socialData['access_token'], $this->socialData['email']);
     *
     * If ($logged) {
     *        $url = Url::fromRoute('<front>');
     *        $event->setRedirect($url);
     *        return;
     *      }
     * }
     * @codingStandardsIgnoreEnd
     */

    }

    if ($this->isMerge) {
      // Assume merge with Traditional account.
      $url = Url::fromRoute('janrain_connect_social.merge_form_signin');

      if ($this->providerName !== JanrainConnectWebServiceConstants::JANRAIN_CONNECT_SOCIAL_CAPTURE) {
        // Merge between Social accounts.
        $url = Url::fromRoute('janrain_connect_social.merge_form', ['provider' => $this->providerName]);
      }

      $event->setRedirect($url);
    }

    // User is not registered. Pre-populate social registration form
    // and allow user to finish registration.
    // Keep the token on form storage. We need this token so we can finish
    // the registration process on Janrain endpoint.
    $this->setDefaultValues($form);
    $event->setForm($form);
  }

  /**
   * Submit Social Registration.
   */
  public function submitRequestToJanrain(JanrainConnectUiSubmitEvent $event) {
    $data = $event->getData();
    $form = $event->getForm();
    $form_state = $event->getFormState();
    $token = $this->session->get('janrain_connect_social_engage_token');

    if ($event->getFormId() == JanrainConnectWebServiceConstants::JANRAIN_CONNECT_SOCIAL_FORM_SIGNIN && !is_null($token)) {
      $this->sendSocialRegistrationToJanrain($token, $data, $form, $form_state);
    }
  }

  /**
   * Checks if user is already registered.
   *
   * @param string $token
   *   The Janrain social token.
   *
   * @return bool
   *   True if is user is already registered. False otherwise.
   */
  private function hasAccount($token) {
    $login_result = $this->janrainConnector->socialLogin(
      $token,
      '',
      $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost()
    );

    if ($login_result['has_errors']) {
      // Verify which errors we received back.
      // We can receive either a non-existing account error
      // or a merge account error.
      switch ($login_result['code']) {
        case JanrainConnectSocialConstants::JANRAIN_CONNECT_SOCIAL_NEW_ACCOUNT_CODE:
          $this->processNewAccount($login_result['user_data']);
          break;

        case JanrainConnectSocialConstants::JANRAIN_CONNECT_SOCIAL_EXISTING_ACCOUNT_CODE:
          $this->isMerge = TRUE;
          $this->providerName = $login_result['existing_provider'];
          break;

        default:
          drupal_set_message($this->t('@message', ['@message' => JanrainConnectConstants::JANRAIN_CONNECT_UNKNOWN_ERROR]), 'error');
          break;
      }
    }
    else {
      $this->socialData = [
        'access_token' => $login_result['access_token'],
        'email' => $login_result['capture_user']->email,
      ];
    }

    return !$login_result['has_errors'];
  }

  /**
   * Store Social Data.
   *
   * Data received from given social media will be stored
   * in class property.
   *
   * @param mixed $user_data
   *   User data.
   */
  private function processNewAccount($user_data) {
    // Temporary store user information as array.
    if (is_object($user_data)) {
      $this->socialData = get_object_vars($user_data);
    }
    else {
      $this->socialData = $user_data;
    }
  }

  /**
   * Set default values on Social Registration Form.
   *
   * @param array $form
   *   Drupal Form array.
   */
  private function setDefaultValues(array &$form) {
    foreach ($form as $field_key => $field) {
      if (!empty($this->socialData[$field_key])) {
        $form[$field_key]['#default_value'] = $this->socialData[$field_key];
      }
    }
  }

  /**
   * Send social registration data to Janrain.
   *
   * @param string $token
   *   Janrain social token.
   * @param array $data
   *   User data to create a janrain registration.
   * @param mixed $form
   *   Form.
   * @param mixed $form_state
   *   Form State.
   */
  private function sendSocialRegistrationToJanrain(string $token, array $data, $form, $form_state) {
    $result = $this->janrainConnector->socialRegister($token, $data);

    if (!$result['has_errors']) {
      // Message success should be translatable @codingStandardsIgnoreLine.
      drupal_set_message($this->t(JanrainConnectConstants::JANRAIN_CONNECT_REGISTRATION_MESSAGE_SUCCESS), 'status');

      // Persist user in Drupal site.
      $status = $this->janrainUsers->persistUser($data, $result, FALSE);

      if ($status == SAVED_NEW) {

        // Welcome Message should be translatable @codingStandardsIgnoreLine.
        drupal_set_message($this->t(JanrainConnectConstants::JANRAIN_CONNECT_REGISTRATION_MESSAGE_WELCOME), 'status');

        $this->requestStack->getCurrentRequest()->query->set('destination', '/');
      }
    }

    // If exists janrain_connect validate, use it to get messages.
    if ($this->moduleHandler->moduleExists('janrain_connect_validate')) {

      // Use direct access because is possible uninstall the Janrain Connect
      // Validate. @codingStandardsIgnoreLine
      $messages = \Drupal::service('janrain_connect_validate.messages_mapping')->getMessages($result, JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FORM_REGISTRATION);

    }
    else {
      $messages[] = JanrainConnectConstants::JANRAIN_CONNECT_REGISTRATION_GENERIC_ERROR;
    }

    foreach ($messages as $message) {
      // @codingStandardsIgnoreLine.
      drupal_set_message($this->t($message), 'error');

      $form_state->setError($form);
    }

    return FALSE;
  }

}
