<?php

namespace Drupal\github_connect\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\github_connect\GithubConnectService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class UsernameChooseForm.
 */
class UsernameChooseForm extends FormBase {

  /**
   * The current account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * GithubController Class Service.
   *
   * @var \Drupal\github_connect\GithubConnectService
   */
  protected $githubConnectService;

  /**
   * Class constructor.
   */
  public function __construct(AccountInterface $account, GithubConnectService $githubConnectService) {
    $this->account = $account;
    $this->githubConnectService = $githubConnectService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
    // Load the service required to construct this class.
      $container->get('current_user'),
      $container->get('github_connect_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'github_connect_username_choose_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $user = '', $token = '') {
    if ($this->account->getAccountName()) {
      $account = $this->account->getAccountName();
    }
    else {
      $account = $user;
    }
    $form['message'] = array(
      '#type' => 'item',
      '#title' => $this->t('Username in use'),
      '#markup' => $this->t('There is already an account associated with your GitHub account name %account_name. Please choose a
        different username for use on %site. This will not change your github username and you will continue to be able
        to log in with your github account.',
        array(
          '%site' => $this->config('system.site')->get('name'),
          '%account_name' => $account,
        )),
    );

    $form['name'] = array('#type' => 'hidden', '#value' => $account->name);
    $form['name_new'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('New username'),
      '#description' => $this->t('Enter another username.'),
      '#required' => TRUE,
    );
    $form['token'] = array('#type' => 'hidden', '#value' => $token);

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array('#type' => 'submit', '#value' => $this->t('Submit username'));

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $name_new = $form_state->getValues()['name_new'];

    if (user_load_by_name($name_new)) {
      $form_state->setErrorByName('name_new', $this->t('This username already exists, please choose another one.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $token = $form_state->getValues()['token'];
    $github_user = $this->githubConnectService->githubConnectGetGithubUserInfo($token);

    // Change the login name to the newly selected name.
    $github_user['login'] = $form_state->getValues()['name_new'];
    $this->githubConnectService->githubConnectRegister($github_user, $token);
    $redirect_url = $this->url('<front>');
    $response = new RedirectResponse($redirect_url);
    $response->send();
    return $response;
  }

}
