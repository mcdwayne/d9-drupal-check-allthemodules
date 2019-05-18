<?php

namespace Drupal\onelogin_integration\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Url;
use Drupal\onelogin_integration\AuthenticationServiceInterface;
use Drupal\onelogin_integration\SAMLAuthenticatorFactoryInterface;
use Drupal\onelogin_integration\UserService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class OneLoginSAMLController.
 *
 * @package Drupal\onelogin_integration\Controller
 */
class OneLoginIntegrationController extends ControllerBase {

  /**
   * Instance of SAMLAuthenticatorFactoryInterface.
   *
   * @var \Drupal\onelogin_integration\SAMLAuthenticatorFactoryInterface
   */
  protected $oneLoginAuthenticationFactory;

  /**
   * The variable that holds an instance of the custom UserService.
   *
   * @var \Drupal\onelogin_integration\UserService
   */
  protected $userService;

  /**
   * The variable that holds an instance of the custom AuthenticationService.
   *
   * @var \Drupal\onelogin_integration\AuthenticationServiceInterface
   */
  protected $authenticationService;

  /**
   * The variable that holds an instance of the AccountProxy class.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $user;

  /**
   * The variable that holds an instance of the ConfigFactoryInterface.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * OneLoginIntegrationController constructor.
   *
   * @param \Drupal\onelogin_integration\SAMLAuthenticatorFactoryInterface $one_login_authenticator_factory
   *   Reference to the SAMLAuthenticatorFactory interface.
   * @param \Drupal\onelogin_integration\AuthenticationServiceInterface $authentication_service
   *   Reference to the AuthenticationServiceInterface interface.
   * @param \Drupal\onelogin_integration\UserService $user_service
   *   Reference to the UserService service.
   * @param \Drupal\Core\Session\AccountProxy $user
   *   Reference to the AccountProxy class.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Reference to the ConfigFactoryInterface.
   *
   * @internal param SAMLAuthenticatorFactoryInterface $one_login_saml_authenticator_factory Reference to the oneLoginSaml2Auth class.*   Reference to the oneLoginSaml2Auth class.
   */
  public function __construct(SAMLAuthenticatorFactoryInterface $one_login_authenticator_factory, AuthenticationServiceInterface $authentication_service, UserService $user_service, AccountProxy $user, ConfigFactoryInterface $config_factory) {
    $this->oneLoginAuthenticationFactory = $one_login_authenticator_factory;
    $this->authenticationService = $authentication_service;
    $this->userService = $user_service;
    $this->user = $user;
    $this->configFactory = $config_factory;
  }

  /**
   * The create method.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Reference to the ContainerInterface interface.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('onelogin_integration.authenticator_factory'),
      $container->get('onelogin_integration.authentication_service'),
      $container->get('onelogin_integration.user_service'),
      $container->get('current_user'),
      $container->get('config.factory')
    );
  }

  /**
   * The SingleSignOn method.
   *
   * Tries to send a request to log the user in.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Returns a RedirectResponse to a specific page or the homepage, regarding
   *   the given settings.
   *
   * @throws \OneLogin\Saml2\Error
   *    Throws Saml2 error.
   */
  public function singleSignOn() {

    if (isset($_GET['destination'])) {
      $target = $_GET['destination'];
    }
    elseif (isset($_GET['returnTo'])) {
      $target = $_GET['returnTo'];
    }

    // TODO: efficienter maken.
    // If a user initiates a login while they are already logged in, simply
    // send them to desired place.
    if ($this->user->id() && !$this->user->isAnonymous()) {
      if (isset($target) && strpos($target, 'onelogin_integration/sso') === FALSE) {
        return new RedirectResponse(Url::fromUri('internal:' . $target));
      }
      else {
        return new RedirectResponse('/');
      }
    }

    if (isset($target) && strpos($target, 'onelogin_integration/sso') === FALSE) {
      $this->oneLoginAuthenticationFactory->createFromSettings()->login($target);
    }
    else {
      $this->oneLoginAuthenticationFactory->createFromSettings()->login();
    }
  }

  /**
   * The Assertion Consumer Service method.
   *
   * Tries to handle the incoming request from the singleSignOn method.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Returns a RedirectResponse to a specific page or the homepage, regarding
   *   the given settings.
   *
   * @throws \OneLogin\Saml2\Error
   *    Throw Saml2 error.
   * @throws \OneLogin\Saml2\ValidationError
   *    Throws Saml2 validation error.
   */
  public function assertionConsumerService() {
    if (isset($_POST['RelayState'])) {
      $target = $_POST['RelayState'];
    }
    elseif (isset($_GET['returnTo'])) {
      $target = $_GET['returnTo'];
    }
    elseif (isset($_GET['destination'])) {
      $target = $_GET['destination'];
    }

    // If a user initiates a login while they are already logged in,
    // simply send them to their profile.
    if ($this->user->id() && !$this->user->isAnonymous()) {
      if (isset($target) && strpos($target, 'onelogin_integration/sso') === FALSE && strpos($target, 'onelogin_integration/acs') === FALSE) {
        return new RedirectResponse($target);
      }
      else {
        return new RedirectResponse('/');
      }
    }
    elseif (isset($_POST['SAMLResponse']) && !empty($_POST['SAMLResponse'])) {
      $this->oneLoginAuthenticationFactory->createFromSettings()->processResponse();

      $errors = $this->oneLoginAuthenticationFactory->createFromSettings()->getErrors();
      if (!empty($errors)) {
        $settings = $this->oneLoginAuthenticationFactory->createFromSettings()->getSettings();
        $debug_error = '';
        if ($settings->isDebugActive()) {
          $debug_error = "<br>" . $this->oneLoginAuthenticationFactory->createFromSettings()->getLastErrorReason();
        }
        drupal_set_message("There was at least one error processing the SAML Response<br>" . implode("<br>", $errors) . $debug_error, 'error', FALSE);
      }
      else {
        $this->authenticationService->processLoginRequest();
      }
    }
    else {
      drupal_set_message("No SAML Response found.", 'error', FALSE);
    }

    if (isset($target) && strpos($target, 'onelogin_integration/sso') === FALSE && strpos($target, 'onelogin_integration/acs') === FALSE) {
      return new RedirectResponse($target);
    }
    else {
      return new RedirectResponse('/');
    }
  }

  /**
   * The singleLogOut method.
   *
   * Takes care of logging the user out.
   *
   * @throws \OneLogin\Saml2\Error
   *    Throws Saml2 error.
   */
  public function singleLogOut() {
    session_destroy();
    $this->oneLoginAuthenticationFactory->createFromSettings()->logout(new RedirectResponse('/'));
  }

  /**
   * Single Log Out service.
   *
   * A service for requests of logging the user out.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Returns a RedirectResponse to a specific page or the homepage, regarding
   *   the given settings.
   *
   * @throws \OneLogin\Saml2\Error
   *    Throws Saml2 error.
   */
  public function singleLogOutService() {
    $this->oneLoginAuthenticationFactory->createFromSettings()->processSLO();
    $errors = $this->oneLoginAuthenticationFactory->createFromSettings()->getErrors();

    if (empty($errors)) {
      @session_destroy();
    }
    else {
      $reason = $this->oneLoginAuthenticationFactory->createFromSettings()->getLastErrorReason();
      drupal_set_message("SLS endpoint found an error." . $reason, 'error', FALSE);
    }

    if (isset($_GET['destination']) && strpos($_GET['destination'], 'user/logout') !== FALSE) {
      unset($_GET['destination']);
    }

    return new RedirectResponse('/');
  }

  /**
   * The metadata method.
   *
   * Returns metadata about the OneLogin configuration.
   *
   * @return string
   *   A URL containing the metadata.
   *
   * @throws \OneLogin\Saml2\Error
   *    Throws Saml2 error.
   */
  public function metadata() {
    $metadata = $this->oneLoginAuthenticationFactory->createFromSettings()->getSettings()->getSPMetadata();

    $response = new Response();
    $response->headers->set('Content-type', 'text/xml');
    $response->setContent($metadata);

    return $response;
  }

  /**
   * The forceUserLogin method.
   *
   * Checks if the 'Force OneLogin' option is checked and redirects accordingly.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A RedirectResponse object with the corresponding route.
   */
  public function forceUserLogin() {
    $force_onelogin = $this->config('onelogin_integration.settings')->get('force_onelogin');

    // If OneLogin is forced, redirect to the OneLogin login page.
    if ($force_onelogin) {
      return new RedirectResponse('onelogin_saml/sso');
    }

    // If OneLogin is not forced, redirect to the normal login page.
    return new RedirectResponse('user/login');
  }

}
