<?php

namespace Drupal\janrain_connect_social\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Drupal\janrain_connect\Service\JanrainConnectConnector;
use Drupal\janrain_connect\Service\JanrainConnectLogin;

/**
 * Form that handles merge existing traditional login with a social.
 */
class TraditionalMergeForm extends FormBase {

  /**
   * Symfony session handler.
   *
   * @var \Symfony\Component\HttpFoundation\Session\Session
   */
  private $session;

  /**
   * JanrainConnectConnector.
   *
   * @var \Drupal\janrain_connect\Service\JanrainConnectConnector
   */
  private $janrainConnector;

  /**
   * JanrainConnectLogin.
   *
   * @var \Drupal\janrain_connect\Service\JanrainConnectLogin
   */
  private $janrainLogin;

  /**
   * {@inheritdoc}
   */
  public function __construct(Session $session, JanrainConnectConnector $janrain_connector, JanrainConnectLogin $janrain_login) {
    $this->session = $session;
    $this->janrainConnector = $janrain_connector;
    $this->janrainLogin = $janrain_login;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('session'),
      $container->get('janrain_connect.connector'),
      $container->get('janrain_connect.login')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'janrain_connect_social_merge';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'janrain_connect.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['message'] = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#value' => $this->t('You already registered via our <strong>traditional registration form</strong>. We can merge your accounts, after you login with your existing account.'),
    ];

    $form['name'] = [
      '#type' => 'email',
      '#title' => $this->t('Email Address'),
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => $this->t('Email Address'),
      ],
    ];

    $form['pass'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => $this->t('Password'),
      ],
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $merge_result = $this->janrainConnector->login(
      $form_state->getValue('name'),
      $form_state->getValue('pass'),
      $this->session->get('janrain_connect_social_engage_token')
    );

    $logged = $this->janrainLogin->mergeLogin($merge_result['access_token'], $merge_result['capture_user']->email);
    $url = Url::fromRoute('<front>');
    if ($logged) {
      // TODO: Improve user journey.
      $form_state->setRedirectUrl($url);
    }

    // TODO: Improve user journey.
    $form_state->setRedirectUrl($url);
  }

}
