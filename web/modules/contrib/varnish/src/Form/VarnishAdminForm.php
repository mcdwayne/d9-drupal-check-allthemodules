<?php

namespace Drupal\varnish\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class VarnishAdminForm extends ConfigFormBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a \Drupal\varnish\Form object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->setConfigFactory($config_factory);
  }


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'varnish_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['varnish.settings'];
  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('varnish.settings');
    // Decide whether or not to flush caches on cron runs.
    // $form['varnish_flush_cron'] = [
    //   '#type' => 'radios',
    //   '#title' => t('Flush page cache on cron?'),
    //   '#options' => [
    //     0 => $this->t('Disabled'),
    //     1 => $this->t('Enabled (with respect for cache_lifetime)'),
    //   ],
    //   '#default_value' => $config->get('varnish_flush_cron'),
    //   '#description' => $this->t('Internally Drupal will attempt to flush
    //     its page cache every time cron.php runs. This can mean too-frequent
    //     cache flushes if you have cron running frequently. NOTE: this cache
    //     flush is global!'),
    //  ];

    $form['varnish_version'] = [
      '#type' => 'select',
      '#title' => $this->t('Varnish version'),
      '#default_value' => $config->get('varnish_version'),
      '#description' => $this->t('Select your varnish version.'),
      '#options' => [
        '3' => '3.x',
        '4' => '4.x',
      ],
    ];

    $form['varnish_control_terminal'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Varnish Control Terminal'),
      '#default_value' => $config->get('varnish_control_terminal'),
      '#required' => TRUE,
      '#description' => $this->t('Set this to the server IP or hostname that varnish runs on (e.g. 127.0.0.1:6082). This must be configured for Drupal to talk to Varnish. Separate multiple servers with spaces.'),
    ];

    $form['varnish_control_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Varnish Control Key'),
      '#default_value' => $config->get('varnish_control_key'),
      '#description' => t('Optional: if you have established a secret key for control terminal access, please put it here.'),
    ];
    $form['varnish_socket_timeout'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Varnish connection timeout (milliseconds)'),
      '#default_value' => $config->get('varnish_socket_timeout'),
      '#description' => $this->t('If Varnish is running on a different server, you may need to increase this value.'),
      '#required' => TRUE,
    ];
    $form['varnish_cache_clear'] = [
      '#type' => 'radios',
      '#title' => $this->t('Varnish Cache Clearing'),
      '#options' => [
        '1' => $this->t('Drupal Default'),
        '0' => $this->t('None'),
      ],
      '#default_value' => $config->get('varnish_cache_clear'),
      '#description' => $this->t('What kind of cache clearing Varnish should utilize. Drupal default will clear all page caches on node updates and cache flush events. None will allow pages to persist for their full max-age; use this if you want to write your own cache-clearing logic.'),
    ];

    // Allow users to select Varnish ban type to use.
    $form['varnish_bantype'] = [
      '#type' => 'select',
      '#title' => $this->t('Varnish ban type'),
      '#default_value' => $config->get('varnish_bantype'),
      '#description' => $this->t('Select the type of varnish ban you wish to use. Ban lurker support requires you to add beresp.http.x-url and beresp.http.x-host entries to the response in vcl_fetch.'),
      '#options' => [
        '0' => $this->t('Normal'),
        '1' => $this->t('Ban Lurker'),
      ],
    ];

    $status = [
      '#theme' => 'varnish_status',
      '#status' => varnish_get_status(),
      '#version' => floatval($config->get('varnish_version')),
    ];
    // Check status.
    $form['varnish_stats'] = [
      '#type' => 'item',
      '#title' => $this->t('Status'),
      '#markup' => \Drupal::service('renderer')->render($status),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $socket_timeout = $form_state->getValue('varnish_socket_timeout');
    if (!is_numeric($socket_timeout) || $socket_timeout < 0) {
      $form_state->setErrorByName('varnish_socket_timeout', $this->t('Varnish connection timeout must be a positive number.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('varnish.settings')
      ->set('varnish_flush_cron', $form_state->getValue('varnish_flush_cron'))
      ->set('varnish_version', $form_state->getValue('varnish_version'))
      ->set('varnish_control_terminal', $form_state->getValue('varnish_control_terminal'))
      ->set('varnish_control_key', $form_state->getValue('varnish_control_key'))
      ->set('varnish_socket_timeout', $form_state->getValue('varnish_socket_timeout'))
      ->set('varnish_cache_clear', $form_state->getValue('varnish_cache_clear'))
      ->set('varnish_bantype', $form_state->getValue('varnish_bantype'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
