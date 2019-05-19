<?php

namespace Drupal\webfactory_master\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Drupal\webfactory\Services\Security;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the form to deploy Satellite entity.
 *
 * @package Drupal\webfactory_master\Form
 */
class SatelliteEntityDeployForm extends EntityConfirmFormBase {

  /**
   * The security service.
   *
   * @var \Drupal\webfactory\Services\Security
   */
  protected $security;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a ChannelEntityEditForm.
   *
   * @param Security $security
   *   The channel source plugin manager.
   * @param StateInterface $state
   *   The state service.
   */
  public function __construct(Security $security, StateInterface $state) {
    $this->security = $security;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('webfactory.services.security'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to deploy %name?', array('%name' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.satellite_entity.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Deploy');
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Database settings form.
    $form['database'] = [
      '#type' => 'details',
      '#title' => t('Database configuration'),
      '#open' => TRUE,
    ];

    $form['database']['dbDriver'] = [
      '#type' => 'select',
      '#title' => t('Driver'),
      '#default_value' => 'mysql',
      '#options' => array('mysql' => 'MySQL'),
      '#description' => $this->t('Database driver.'),
      '#required' => TRUE,
    ];

    $form['database']['masterUsername'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Privileged account login'),
      '#maxlength' => 255,
      '#description' => $this->t('This account has CREATE/DROP DATABASE permission'),
      '#default_value' => 'root',
      '#required' => TRUE,
    ];

    $form['database']['masterPassword'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Privileged account password'),
      '#maxlength' => 255,
      '#description' => $this->t('This account has CREATE/DROP DATABASE permission'),
    ];

    $form['database']['dbUsername'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#maxlength' => 255,
      '#description' => $this->t('Database username.'),
      '#default_value' => $this->entity->id(),
      '#required' => TRUE,
    ];
    $form['database']['dbPassword'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#maxlength' => 255,
      '#description' => $this->t('Database username password.'),
      '#default_value' => $this->entity->id(),
    ];

    $form['database']['dbHost'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Host'),
      '#maxlength' => 255,
      '#description' => $this->t('Database host.'),
      '#default_value' => 'localhost',
      '#required' => TRUE,
    ];

    $form['database']['dbPort'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Port'),
      '#maxlength' => 255,
      '#description' => $this->t('Database port.'),
      '#default_value' => '3306',
      '#required' => TRUE,
    ];

    $form['database']['dbDatabase'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Database name'),
      '#maxlength' => 255,
      '#description' => $this->t('Database name.'),
      '#default_value' => $this->entity->id(),
      '#required' => TRUE,
    ];
    $form['database']['dbPrefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Prefix'),
      '#maxlength' => 255,
      '#description' => $this->t('Database prefix.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->markAsPending();
    $this->entity->save();

    $token = $this->security->generateToken();
    $this->state->set($this->entity->id() . '.install_info', [
      'token' => $token,
      'db_info' => [
        'master_login' => $form_state->getValue('masterUsername'),
        'master_pwd'   => $form_state->getValue('masterPassword'),
        'database'     => $form_state->getValue('dbDatabase'),
        'host'         => $form_state->getValue('dbHost'),
        'port'         => $form_state->getValue('dbPort'),
        'username'     => $form_state->getValue('dbUsername'),
        'password'     => $form_state->getValue('dbPassword'),
        'driver'       => $form_state->getValue('dbDriver'),
        'db_prefix'    => $form_state->getValue('dbPrefix'),
      ],
    ]);

    $this->asyncInstallProcess($this->entity->id(), $token);

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

  /**
   * Send a async http install request to install-site.php.
   *
   * @param string $sat_id
   *   The satellite ID to install.
   * @param string $token
   *   Unique install token.
   */
  protected function asyncInstallProcess($sat_id, $token) {
    $post_data = 'sat_id=' . $sat_id . '&token=' . $token;

    $host = $_SERVER['HTTP_HOST'];
    $port = 80;

    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
      $host = 'ssl://' . $host;
      $port = 443;
    }

    /*
     * Initiates a connection to example.com using port 80 with a timeout
     * of 15 seconds.
     */
    $socket = fsockopen($host, $port, $errno, $errstr, 1);

    // Checks if the connection was fine.
    if (!$socket) {
      /*
       * Connection failed so we display the error number and message and stop
       * the script from continuing.
       */
      echo 'error: ' . $errno . ' ' . $errstr;
      die;
    }
    else {
      /*
       * Builds the header data we will send along with are post data.
       * This header data tells the web server we are connecting to what we are,
       * what we are requesting and the content type so that it can process are
       * request.
       */
      $http  = "POST /install-site.php HTTP/1.1\r\n";
      $http .= "Host: " . $host . "\r\n";
      $http .= "User-Agent: " . $_SERVER['HTTP_USER_AGENT'] . "\r\n";
      $http .= "Content-Type: application/x-www-form-urlencoded\r\n";
      $http .= "Content-length: " . strlen($post_data) . "\r\n";
      $http .= "Connection: close\r\n\r\n";
      $http .= $post_data . "\r\n\r\n";

      fwrite($socket, $http);
      fclose($socket);
    }
  }

}
