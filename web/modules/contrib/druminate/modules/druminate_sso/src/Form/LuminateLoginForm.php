<?php

namespace Drupal\druminate_sso\Form;

use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\druminate_sso\Event\DruminateSsoEvents;
use Drupal\druminate_sso\Event\DruminateSsoPreLoginEvent;
use Drupal\externalauth\ExternalAuth;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\druminate\Plugin\DruminateEndpointManager;
use Drupal\Core\Url;

/**
 * Class LuminateLoginForm.
 *
 * This class borrows heavily from the User module.
 *
 * @see \Drupal\user\Form\UserLoginForm
 */
class LuminateLoginForm extends FormBase {

  /**
   * The External Authentication service.
   *
   * @var \Drupal\externalauth\ExternalAuth
   */
  protected $externalAuth;

  /**
   * The Druminate endpoint plugin manager service.
   *
   * @var \Drupal\druminate\Plugin\DruminateEndpointManager
   */
  protected $druminateEndpointManager;

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcher
   */
  protected $dispatcher;

  /**
   * The flood service.
   *
   * @var \Drupal\Core\Flood\FloodInterface
   */
  protected $flood;

  /**
   * The SSO Drupal configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $ssoConfig;

  /**
   * Constructs a new LuminateLoginForm object.
   *
   * @param \Drupal\druminate\Plugin\DruminateEndpointManager $druminate_endpoint_manager
   *   The Druminate endpoint plugin manager service.
   * @param \Drupal\externalauth\ExternalAuth $external_auth
   *   The External Auth service.
   * @param \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher $dispatcher
   *   The event dispatching service.
   * @param \Drupal\Core\Flood\FloodInterface $flood
   *   The flood service.
   */
  public function __construct(DruminateEndpointManager $druminate_endpoint_manager, ExternalAuth $external_auth, ContainerAwareEventDispatcher $dispatcher, FloodInterface $flood) {
    $this->druminateEndpointManager = $druminate_endpoint_manager;
    $this->externalAuth = $external_auth;
    $this->dispatcher = $dispatcher;
    $this->flood = $flood;
    $this->ssoConfig = $this->configFactory()->get('druminate_sso.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.druminate_endpoint'),
      $container->get('externalauth.externalauth'),
      $container->get('event_dispatcher'),
      $container->get('flood')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'luminate_login_form';
  }

  /**
   * Builds the establishSession servlet URL.
   *
   * @param string $uri
   *   The establish_session_uri value from the Config Form.
   * @param string $rid
   *   The routing ID from the LO login response.
   * @param string $nonce
   *   The nonce for the LO login response.
   * @param string $destination
   *   The destination to redirect the user to.
   *
   * @return string
   *   The Establish Session URL.
   */
  public static function buildEstablishSessionUrl($uri, $rid, $nonce, $destination) {
    return "{$uri};jsessionid={$rid}?NONCE_TOKEN={$nonce}&NEXTURL={$destination}";
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['username'] = [
      '#maxlength' => USERNAME_MAX_LENGTH,
      '#size' => 60,
      '#weight' => '0',
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#required' => TRUE,
      '#attributes' => [
        'autocorrect' => 'none',
        'autocapitalize' => 'none',
        'spellcheck' => 'false',
        'autofocus' => 'autofocus',
      ],
    ];
    $form['pass'] = [
      '#type' => 'password',
      '#description' => $this->t('Forgot your password? <a href="@druminate-reset">Reset it here.</a>', ['@druminate-reset' => '/druminate/password']),
      '#title' => $this->t('Password'),
      '#size' => 60,
      '#weight' => '1',
      '#required' => TRUE,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Log in'),
    ];

    $form['#validate'][] = '::validateAuthentication';
    $form['#validate'][] = '::validateFinal';

    return $form;
  }

  /**
   * Authenticate user using Convio's Luminate Online CRM API.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\externalauth\Exception\ExternalAuthRegisterException
   */
  public function validateAuthentication(array $form, FormStateInterface $form_state) {
    $config = $this->config('druminate_sso.settings');
    $params = [
      'password' => $form_state->getValue('pass'),
      'user_name' => $form_state->getValue('username'),
    ];

    // Do not allow any login from the current user's IP if the limit has been
    // reached. Default is 50 failed attempts allowed in one hour. This is
    // independent of the per-user limit to catch attempts from one IP to log
    // in to many different user accounts.  We have a reasonably high limit
    // since there may be only one apparent IP for all users at an institution.
    // @see Drupal\user\Form\UserLoginForm::validateAuthentication.
    $flood_config = $this->config('user.flood');
    if (!$this->flood->isAllowed('user.failed_login_ip', $flood_config->get('ip_limit'), $flood_config->get('ip_window'))) {
      $form_state->set('flood_control_triggered', 'ip');
      return;
    }

    // Attempt to authenticate to Luminate Online.
    /** @var \Drupal\Druminate\Plugin\DruminateEndpointInterface $login */
    $login = $this->druminateEndpointManager->createInstance('sso_login', $params);
    $data = $login->loadData();

    // Parse login response. If a loginResponse class exists on the data we
    // succeeded in authenticating with Convio.
    if (is_object($data) && isset($data->loginResponse)) {
      $cons_id = $data->loginResponse->cons_id;
      // Do not store the password used in the LO form. This will prevent users
      // from logging into Drupal directly with the same password as LO.
      $user_params = [
        'pass' => \user_password(255),
        'name' => $cons_id,
      ];
      $account = $this->externalAuth->load($user_params['name'], 'druminate_sso');

      if (empty($account)) {
        if ($config->get('role.deny_no_match')) {
          $form_state->setErrorByName('username', $this->t('User registration denied. Please see your Administrator.'));
          return;
        }
        $account = $this->externalAuth->register($user_params['name'], 'druminate_sso', $user_params);
      }

      if ($flood_config->get('uid_only')) {
        // Register flood events based on the uid only, so they apply for any
        // IP address. This is the most secure option.
        $identifier = $account->id();
      }
      else {
        // The default identifier is a combination of uid and IP address. This
        // is less secure but more resistant to denial-of-service attacks that
        // could lock out all users with public user names.
        $identifier = $account->id() . '-' . $this->getRequest()->getClientIP();
      }
      $form_state->set('flood_control_user_identifier', $identifier);

      // Don't allow login if the limit for this user has been reached.
      // Default is to allow 5 failed attempts every 6 hours.
      if (!$this->flood->isAllowed('user.failed_login_user', $flood_config->get('user_limit'), $flood_config->get('user_window'), $identifier)) {
        $form_state->set('flood_control_triggered', 'user');
        return;
      }

      // Fire our external authentication pre-login event. This allows
      // subscribers to alter the user account prior to logging in, such as to
      // add user roles. Subscribers can prevent logging in by setting the
      // authRestricted property to TRUE.
      // @see Drupal\druminate_sso\Subscriber\DruminateSsoPreLoginSubscriber
      /** @var \Drupal\druminate_sso\Event\DruminateSsoPreLoginEvent $event */
      $event = $this->dispatcher->dispatch(DruminateSsoEvents::PRE_LOGIN_EVENT, new DruminateSsoPreLoginEvent($account, 'druminate_sso', $cons_id, $data->loginResponse->token, $data->loginResponse->nonce, $cons_id));

      // Save the user account since a subscriber may have altered it.
      $account->save();

      if ($event->isAuthRestricted()) {
        $form_state->setErrorByName('username', $this->t('Login prevented by a subscriber.'));
        return;
      }

      // Log the successful login of the user if debugging is enabled.
      if ($config->get('debug')) {
        $this->getLogger('druminate_sso')
          ->info($this->t('Druminate constituent %constituent has logged in as Drupal user %user.', [
            '%constituent' => $cons_id,
            '%user' => "{$account->label()}:({$account->id()})",
          ]));
      }
    }
    // An errorResponse in $data means we failed to login for some reason.
    elseif (is_object($data) && isset($data->errorResponse)) {
      switch ($data->errorResponse->code) {
        case 202:
          $message = $this->t('Invalid username or password.');
          break;

        default:
          $message = $this->t('An error occurred. Please see your Administrator and mention error code: %error', ['%error' => $data->errorResponse->code]);
      }
      $form_state->setErrorByName('username', $message);
      $this->getLogger('druminate_sso')->error($message);
      return;
    }
    // Catch the false return by the Druminate Endpoint.
    elseif (!$data) {
      $form_state->setErrorByName('username', $this->t('An unknown error occurred. Please see your Administrator.'));
      return;
    }

    // If establishSession is enabled set the appropriate redirect url.
    if ($this->ssoConfig->get('establish_session_enabled') && !empty($cons_id)) {
      $uri = $this->ssoConfig->get('establish_session_uri');
      if (!$uri) {
        $this->getLogger('druminate_sso')
          ->error($this->t('Establish session URI is not set. Please set the URI at %url.', ['%url' => '/admin/config/druminate/druminate_sso/config']));
        $form_state->setErrorByName('username', $this->t('An error occurred. Please see your Administrator.'));
        return;
      }
      $destination = Url::fromRoute('entity.user.canonical', ['user' => $account->id()])
        ->setAbsolute();
      // We need a fresh auth token for the establishSession method to function.
      $token_endpoint = $this->druminateEndpointManager->createInstance('sso_token', ['cons_id' => $cons_id]);
      $sso_token_data = $token_endpoint->loadData();
      if (is_object($sso_token_data) && isset($sso_token_data->getSingleSignOnTokenResponse)) {
        // Save URL in form_state for redirecting in submit handler.
        $form_state->set('esurl', static::buildEstablishSessionUrl($uri, $sso_token_data->getSingleSignOnTokenResponse->routing_id, $sso_token_data->getSingleSignOnTokenResponse->nonce, $destination->toString()));
      }
    }
    // Set the user id on the form state for later page redirection.
    $form_state->set('uid', $account->id());

    // Log the user into Drupal.
    $this->externalAuth->userLoginFinalize($account, $user_params['name'], 'druminate_sso');
    // Do not add any further code below this line--userLoginFinalize needs to
    // be the last thing we call in this method.
  }

  /**
   * Checks if user was not authenticated, or if too many logins were attempted.
   *
   * This validation function should always be the last one.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @see \Drupal\user\Form\UserLoginForm::validateFinal()
   */
  public function validateFinal(array $form, FormStateInterface $form_state) {
    $flood_config = $this->config('user.flood');
    if (!$form_state->get('uid')) {
      // Always register an IP-based failed login event.
      $this->flood->register('user.failed_login_ip', $flood_config->get('ip_window'));
      // Register a per-user failed login event.
      if ($flood_control_user_identifier = $form_state->get('flood_control_user_identifier')) {
        $this->flood->register('user.failed_login_user', $flood_config->get('user_window'), $flood_control_user_identifier);
      }

      if ($flood_control_triggered = $form_state->get('flood_control_triggered')) {
        if ($flood_control_triggered == 'user') {
          $form_state->setErrorByName('name', $this->formatPlural($flood_config->get('user_limit'), 'There has been more than one failed login attempt for this account. It is temporarily blocked. Try again later or contact your Administrator.', 'There have been more than @count failed login attempts for this account. It is temporarily blocked. Try again later or contact your Administrator.'));
        }
        else {
          // We did not find a uid, so the limit is IP-based.
          $form_state->setErrorByName('name', $this->t('Too many failed login attempts from your IP address. This IP address is temporarily blocked. Try again later or contact your Administrator.', [':url' => $this->url('user.pass')]));
        }
      }
    }
    elseif ($flood_control_user_identifier = $form_state->get('flood_control_user_identifier')) {
      // Clear past failures for this user so as not to block a user who might
      // log in and out more than once in an hour.
      $this->flood->clear('user.failed_login_user', $flood_control_user_identifier);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $uid = $form_state->get('uid');
    if ($esurl = $form_state->get('esurl')) {
      $form_state->setResponse(new TrustedRedirectResponse($esurl));
    }
    elseif (!$this->getRequest()->request->has('destination')) {
      $form_state->setRedirect(
        'entity.user.canonical',
        ['user' => $uid]
      );
    }
    else {
      // A destination was set, probably on an exception controller.
      $this->getRequest()->query->set('destination', $this->getRequest()->request->get('destination'));
    }
  }

}
