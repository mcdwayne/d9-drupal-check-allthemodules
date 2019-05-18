<?php

namespace Drupal\github_connect\Form;

use Drupal\Core\Password\PasswordInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\github_connect\GithubConnectService;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Validates user authentication credentials.
 */
class VerifyEmailForm extends FormBase {

  /**
   * The password hashing service.
   *
   * @var \Drupal\Core\Password\PasswordInterface
   */
  protected $passwordChecker;

  /**
   * The current account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The GithubConnect Service.
   *
   * @var \Drupal\github_connect\GithubConnectService
   */
  protected $githubConnectService;

  /**
   * VerifyEmailForm constructor.
   *
   * @param \Drupal\Core\Password\PasswordInterface $passwordChecker
   *   The password hashing service.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current account.
   * @param \Drupal\github_connect\GithubConnectService $githubConnectService
   *   The GithubConnect Service.
   */
  public function __construct(PasswordInterface $passwordChecker, AccountInterface $account, GithubConnectService $githubConnectService) {
    $this->passwordChecker = $passwordChecker;
    $this->account = $account;
    $this->githubConnectService = $githubConnectService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('password'),
      $container->get('current_user'),
      $container->get('github_connect_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'github_connect_verify_email_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $uid = '', $token = '') {
    $site_name = $this->config('system.site')->get('name');
    if (!$uid) {
      $account = $this->account;
    }
    else {
      // Pass your uid.
      $account = User::load($uid);
    }
    $name = $account->getAccountName();
    $form['message'] = array(
      '#type' => 'item',
      '#title' => $this->t('Email address in use'),
      '#markup' => $this->t('There is already an account associated with your GitHub email address. Type your %site account password to merge accounts.', array('%site' => $site_name)),
    );
    $form['name'] = array('#type' => 'hidden', '#value' => $name);
    $form['pass'] = array(
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#description' => $this->t('Enter your password.'),
      '#required' => TRUE,
    );
    $form['token'] = array('#type' => 'hidden', '#value' => $token);
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array('#type' => 'submit', '#value' => $this->t('Merge accounts'));
    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $name = $form_state->getValues()['name'];
    $password = $form_state->getValues()['pass'];

    if ($this->githubConnectAuthenticateDrupal($name, $password) == FALSE) {
      $form_state->setErrorByName('pass', $this->t('Incorrect password.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $account = user_load_by_name($form_state->getValue('name'));
    $token = $form_state->getValue('token');
    $this->githubConnectService->githubConnectSaveGithubUser($account, $token);

    // Log in the connected user.
    $this->githubConnectService->githubConnectUserLogin($account);
    drupal_set_message($this->t('You are now connected with your GitHub account.'));

    $redirect_url = $this->url('<front>');
    $response = new RedirectResponse($redirect_url);
    $response->send();
    return $response;
  }

  /**
   * Authenticates if password being entered matches with the correct password.
   *
   * @param string $name
   *   The user name to authenticate.
   * @param string $password
   *   A plain-text password, such as trimmed text from form values.
   *
   * @return int|bool
   *   The user's uid on success, or FALSE on failure to authenticate.
   */
  public function githubConnectAuthenticateDrupal($name, $password) {
    $uid = FALSE;
    if (!empty($name) && !empty($password)) {
      $account = user_load_by_name($name);
      if ($account) {
        // Allow alternate password hashing schemes.
        if ($this->passwordChecker->check($password, $account->getPassword())) {
          // Successful authentication.
          $uid = $account->id();
        }
      }
    }
    return $uid;
  }

}
