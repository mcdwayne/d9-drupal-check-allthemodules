<?php

namespace Drupal\matrix_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\matrix_api\MatrixClient;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MatrixApiSettingsForm.
 *
 * @package Drupal\matrix_api\Form
 */
class MatrixApiSettingsForm extends ConfigFormBase {

  /**
   * Drupal\matrix_api\MatrixClient definition.
   *
   * @var \Drupal\matrix_api\MatrixClient
   */
  protected $matrixClient;

  /**
   *
   */
  public function __construct(
      ConfigFactoryInterface $config_factory,
      MatrixClient $matrixClient
    ) {
    parent::__construct($config_factory);
    $this->matrixClient = $matrixClient;
  }

  /**
   *
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('matrix_api.matrixclient')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'matrix_api.MatrixApiSettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'matrix_api_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('matrix_api.MatrixApiSettings');
    $form['home_server_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Home Server URL'),
      '#description' => $this->t('Provide a URL for the homeserver you wish to connect to. e.g. https://matrix.org. Do not include a trailing slash. Include a port if necessary.'),
      '#default_value' => $config->get('matrix_api.home_server_url'),
    ];
    $form['token'] = [
      '#type' => 'password',
      '#title' => $this->t('Token'),
      '#description' => $this->t('Set a token for authentication'),
      '#maxlength' => 255,
      '#size' => 64,
      '#default_value' => $config->get('matrix_api.token'),
    ];
    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#description' => $this->t('Username to generate a token. Optional - if changed, will only be used to generate a new authentication token.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('matrix_api.username'),
    ];
    $form['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#description' => $this->t('Used to generate a new authorization token'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('matrix_api.password'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    if ($form_state->getValue('token') && $form_state->getValue('password')) {
      $form_state->setErrorByName('token', 'Set only one of Token, Password. User/Password is used to generate a token.');
      $form_state->setErrorByName('password');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('matrix_api.MatrixApiSettings')
      ->set('matrix_api.home_server_url', $form_state->getValue('home_server_url'))
      ->save();
    if ($token = $form_state->getValue('token')) {
      $this->config('matrix_api.MatrixApiSettings')
        ->set('matrix_api.token', $token)
        ->save();
    }
    elseif ($password = $form_state->getValue('password')) {
      $user = $form_state->getValue('username');
      $token = $this->loginToken($user, $password);
      if ($token) {
        $this->config('matrix_api.MatrixApiSettings')
          ->set('matrix_api.username', $user)
          ->set('matrix_api.password', $password)
          ->set('matrix_api.token', $token)
          ->save();
      }

    }
    drupal_set_message('Matrix API Settings updated.');
  }

  /**
   * Attempt to retrieve a login token from Matrix.
   *
   * Format: POST /_matrix/client/r0/login.
   *
   * {
   *   "type": "m.login.password",
   *   "user": "cheeky_monkey",
   *   "password": "ilovebananas"
   * }
   */
  protected function loginToken($username, $password) {

    try {
      $token = $this->matrixClient->login($username, $password);
    }
    catch (ClientException $e) {
      switch ($e->getCode()) {
        case '400':
          drupal_set_message('There was a problem reaching the Matrix server. 
            Check to see if there is a trailing slash in the Home Server config. 
            Message: ' . $e->getMessage(), 'error');
          break;

        case '403':
          drupal_set_message('Authentication failed. Username or Password not recognized.', error);
          break;

        case '404':
          drupal_set_message('Matrix API not found. Is the Home Server set correctly?', 'error');
        default:
          drupal_set_message('Client Exception: ' . $e->getMessage(), 'error');
      }
      return FALSE;
    }
    catch (\Exception $e) {
      drupal_set_message('Exception: ' . $e->getMessage(), 'error');
      return FALSE;
    }

    return $token;

  }

}
