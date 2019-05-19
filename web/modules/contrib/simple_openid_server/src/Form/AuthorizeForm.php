<?php

namespace Drupal\simple_openid_server\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\user\UserAuthInterface;
use Firebase\JWT\JWT;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Creates authorize form for OpenId.
 */
class AuthorizeForm extends FormBase {

  /**
   * The user authentication object.
   *
   * @var \Drupal\user\UserAuthInterface
   */
  protected $userAuth;

  /**
   * Constructs a new login form.
   *
   * @param \Drupal\user\UserAuthInterface $user_auth
   *   The user authentication object.
   */
  public function __construct(UserAuthInterface $user_auth) {
    $this->userAuth = $user_auth;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('user.auth'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simple_openid_server_login';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $response_type = $this->getRequest()->query->get('response_type');
    $client_id = $this->getRequest()->query->get('client_id');
    $redirect_uri = $this->getRequest()->query->get('redirect_uri');
    $state = $this->getRequest()->query->get('state');

    if (!isset($redirect_uri)) {
      return new JsonResponse(array('error' => 'invalid_request'), JsonResponse::HTTP_BAD_REQUEST);
    }
    else if ($response_type !== 'code' || $client_id !== $this->getClientId()) {
      return new TrustedRedirectResponse($redirect_uri . '?error=invalid_request&state=' . $state);
    }

    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#size' => 60,
      '#maxlength' => USERNAME_MAX_LENGTH,
      '#description' => $this->t('Enter your username.'),
      '#required' => TRUE,
      '#attributes' => array(
        'autocorrect' => 'none',
        'autocapitalize' => 'none',
        'spellcheck' => 'false',
        'autofocus' => 'autofocus',
      ),
    );

    $form['pass'] = array(
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#size' => 60,
      '#description' => $this->t('Enter yout password.'),
      '#required' => TRUE,
    );

    $form['actions'] = array('#type' => 'actions', 'submit' => array('#type' => 'submit', '#value' => $this->t('Log in')));

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $uid = $this->userAuth->authenticate($form_state->getValue('name'), trim($form_state->getValue('pass')));
    if (!$uid) {
      $form_state->setErrorByName('name', $this->t('Unrecognized username or password.'));
    }
    else {
      $form_state->set('uid', $uid);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $sub = $form_state->get('uid');
    $client_id = $this->getRequest()->query->get('client_id');
    $redirect_uri = $this->getRequest()->query->get('redirect_uri');
    $state = $this->getRequest()->query->get('state');

    $code = array('sub' => $sub, 'client_id' => $client_id, 'redirect_uri' => $redirect_uri);

    $form_state->setResponse(new TrustedRedirectResponse($redirect_uri . '?code='.JWT::encode($code, 'code_secret').'&state=' . $state));
  }

  protected function getClientId() {
    return \Drupal::config('simple_openid_server.settings')->get('client_id');
  }
}
