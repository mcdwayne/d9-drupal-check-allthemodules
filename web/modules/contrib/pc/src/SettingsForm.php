<?php

namespace Drupal\pc;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Builds and process a form for editing a PHP Console settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The request stack.
   *
   * @var \Drupal\pc\ConnectorFactory
   */
  protected $connectorFactory;

  /**
   * Constructs a SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\pc\ConnectorFactory $connector_factory
   *   The connector factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RequestStack $request_stack, ConnectorFactory $connector_factory) {
    parent::__construct($config_factory);
    $this->requestStack = $request_stack;
    $this->connectorFactory = $connector_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('request_stack'),
      $container->get('pc.connector_factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pc_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (!class_exists('PhpConsole\Connector')) {
      drupal_set_message($this->t('PHP Console library is not installed.'), 'warning');
    }

    $settings = $this->config('pc.settings');

    $ip = $this->requestStack->getCurrentRequest()->getClientIp();

    if (!$form_state->getUserInput()) {
      if (!$this->checkIp($ip)) {
        drupal_set_message($this->t('Your current IP address %ip is not allowed to access to PHP Console.', ['%ip' => $ip]), 'warning', FALSE);
      }
      else {
        /** @var \PhpConsole\Connector $connector */
        $connector = $this->connectorFactory->get();
        if ($connector && !$connector->isActiveClient()) {
          $url = Url::fromUri(
            'https://chrome.google.com/webstore/detail/php-console/nfhmhhlpfleoednkpnnnkolmclajemef',
            ['attributes' => ['target' => 'blank']]
          );
          $extension_link = $this->l($this->t('PHP Console extension'), $url);
          drupal_set_message($this->t('You need @extension_link to be installed on Google Chrome.', ['@extension_link' => $extension_link]), 'warning', FALSE);
        }
      }
    }

    $form['password_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable password protection'),
      '#description' => $this->t('Remote PHP code execution allowed only in password protected mode.'),
      '#default_value' => $settings->get('password_enabled'),
    ];

    // Password field type doesn't support form #states. So we make a wrapper
    // around the field.
    // See https://drupal.org/node/1427838.
    $form['pass_wrapper'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="password_enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['pass_wrapper']['password'] = [
      '#title' => 'Password',
      '#type' => 'password',
      '#description' => $this->t('Provide a password for client authorization.'),
    ];

    $form['remote_php_execution'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable remote PHP code execution'),
      '#default_value' => $settings->get('remote_php_execution'),
      '#description' => $this->t('Note that it is a dangerous security risk in the hands of a malicious or inexperienced user.'),
      '#states' => [
        'visible' => [
          ':input[name="password_enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['ips'] = [
      '#type' => 'textarea',
      '#title' => 'Allowed IP masks',
      '#description' => $this->t('Enter one value per line. Leave empty to disable IP verification.'),
      '#default_value' => $settings->get('ips'),
    ];
    $form['ips']['#description'] .= ' ' . $this->t('Your IP address is: %ip.', ['%ip' => $ip]);

    $form['track_errors'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Handle PHP errors'),
      '#default_value' => $settings->get('track_errors'),
      '#description' => $this->t('This option does not cancel default Drupal error handler.'),
    ];

    $form['dumper_maximum_depth'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum dumper depth'),
      '#default_value' => $settings->get('dumper_maximum_depth'),
      '#description' => $this->t('Maximum depth that the dumper should go into the variable.'),
      '#min' => 1,
      '#max' => 100,
    ];

    $options = [
      'server' => $this->t('Server'),
      'session' => $this->t('Session'),
      'cookie' => $this->t('Cookie'),
      'post' => $this->t('Post'),
      'get' => $this->t('Get'),
      'logged_user' => $this->t('Logged user'),
      'route' => $this->t('Current route'),
      'forms' => $this->t('Forms'),
      'memory_usage' => $this->t('Memory usage'),
      'peak_memory_usage' => $this->t('Peak memory usage'),
      'execution_time' => $this->t('Page execution time'),
      'db_queries' => $this->t('DB queries'),
      'watchdog' => $this->t('Watchdog messages'),
      'emails' => $this->t('Outgoing emails'),
    ];
    $form['debug_info'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Debug information'),
      '#options' => $options,
      '#default_value' => array_keys(array_filter($settings->get('debug_info'))),
      '#description' => $this->t('These data will be sent to browser console on each page request.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $settings = $this->configFactory()->getEditable('pc.settings');
    $values = $form_state->getValues();
    if ($values['password']) {
      $settings->set('password', $values['password']);
    }
    $settings
      ->set('password_enabled', $values['password_enabled'])
      ->set('remote_php_execution', $values['remote_php_execution'])
      ->set('ips', $values['ips'])
      ->set('track_errors', $values['track_errors'])
      ->set('dumper_maximum_depth', $values['dumper_maximum_depth'])
      ->set('debug_info', $values['debug_info'])
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Check whether the IP is allowed to connect to PHP Console.
   */
  protected function checkIp($ip) {
    $ips = explode("\n", $this->config('pc.settings')->get('ips'));
    $ips = array_map('trim', $ips);
    $ips = array_filter($ips, 'strlen');

    // Empty $ips means any IPs are allowed.
    if (!$ips) {
      return TRUE;
    }

    foreach ($ips as $allowed_ip_mask) {
      if (preg_match('~^' . str_replace(['.', '*'], ['\.', '\w+'], $allowed_ip_mask) . '$~i', $ip)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['pc.settings'];
  }

}
