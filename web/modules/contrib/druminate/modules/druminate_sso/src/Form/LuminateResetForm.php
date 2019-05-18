<?php

namespace Drupal\druminate_sso\Form;

use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\externalauth\ExternalAuth;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\druminate\Plugin\DruminateEndpointManager;

/**
 * Class LuminateLoginForm.
 *
 * This class borrows heavily from the User module.
 *
 * @see \Drupal\user\Form\UserLoginForm
 */
class LuminateResetForm extends FormBase {

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
    return 'luminate_reset_form';
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
      '#title' => $this->t('Email'),
      '#required' => TRUE,
      '#attributes' => [
        'autocorrect' => 'none',
        'autocapitalize' => 'none',
        'spellcheck' => 'false',
        'autofocus' => 'autofocus',
      ],
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    $form['#validate'][] = '::validatePasswordResetRequest';
    honeypot_add_form_protection($form, $form_state);
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
  public function validatePasswordResetRequest(array $form, FormStateInterface $form_state) {
    $params = [
      'send_user_name' => 'true',
      'email' => $form_state->getValue('username'),
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
      \Drupal::messenger()
        ->addStatus('Further instructions have been sent to your e-mail address.');
      return;
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
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
