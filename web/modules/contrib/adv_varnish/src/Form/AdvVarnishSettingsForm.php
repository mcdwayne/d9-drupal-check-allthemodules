<?php

/**
 * @file
 * Contains \Drupal\adv_varnish\Form\AdvVarnishSettingsForm.
 */

namespace Drupal\adv_varnish\Form;

use Drupal\adv_varnish\AdvVarnishInterface;
use Drupal\adv_varnish\VarnishInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Render\Element\StatusMessages;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Datetime\DateFormatter;

/**
 * Configure varnish settings for this site.
 */
class AdvVarnishSettingsForm extends ConfigFormBase {

  /**
   * Stores the state storage service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;
  protected $varnishHandler;

  /**
   * Constructs a AdvVarnishSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key value store.
   */
  public function __construct(ConfigFactoryInterface $config_factory, StateInterface $state, VarnishInterface $varnish_handler, DateFormatter $date_formatter) {
    parent::__construct($config_factory);
    $this->state = $state;
    $this->varnishHandler = $varnish_handler;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('state'),
      $container->get('adv_varnish.handler'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'adv_varnish_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['adv_varnish.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('adv_varnish.settings');

    $form['adv_varnish'] = [
      '#tree' => TRUE,
    ];

    // Display module status.
    $backend_status = $this->varnishHandler->varnishGetStatus();

    $_SESSION['messages'] = [];
    if (empty($backend_status)) {
      drupal_set_message(t('Varnish backend is not set.'), 'warning');
    }
    else {
      foreach ($backend_status as $backend => $status) {
        if (empty($status)) {
          drupal_set_message(t('Varnish at !backend not responding.', ['!backend' => $backend]), 'error');
        }
        else {
          drupal_set_message(t('Varnish at !backend connected.', ['!backend' => $backend]));
        }
      }
    }

    $form['adv_varnish']['general'] = array(
      '#title' => t('General settings'),
      '#type' => 'details',
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );

    $form['adv_varnish']['general']['logging'] = array(
      '#type' => 'checkbox',
      '#title' => t('Logging'),
      '#default_value' => $config->get('general.logging'),
      '#description' => t('Check, if you want to log vital actions to watchdog.'),
    );

    $form['adv_varnish']['general']['debug'] = array(
      '#type' => 'checkbox',
      '#title' => t('Debug mode'),
      '#default_value' => $config->get('general.debug'),
      '#description' => t('Check if you want to add debug info.'),
    );

    $form['adv_varnish']['general']['noise'] = array(
      '#type' => 'textfield',
      '#title' => t('Hashing Noise'),
      '#default_value' => $config->get('general.noise'),
      '#description' => t('This works as private key, you can change it at any time.'),
    );

    $options = array(10, 30, 60, 120, 300, 600, 900, 1800, 3600);
    $options = array_map(array($this->dateFormatter, 'formatInterval'), array_combine($options, $options));
    $options[0] = t('No Grace (bad idea)');
    $grace_hint = t("Grace in the scope of Varnish means delivering otherwise
      expired objects when circumstances call for it.
      This can happen because the backend-director selected is down or a
      different thread has already made a request to the backend
      that's not yet finished."
    );
    $form['adv_varnish']['general']['grace'] = array(
      '#title' => t('Grace'),
      '#type' => 'select',
      '#options' => $options,
      '#description' => $grace_hint,
      '#default_value' => $config->get('general.grace'),
    );

    // Cache time for Varnish.
    $period = array(0, 60, 180, 300, 600, 900, 1800,
      2700, 3600, 10800, 21600, 32400, 43200, 86400,
    );
    $period = array_map(array($this->dateFormatter, 'formatInterval'), array_combine($period, $period));
    $period[0] = $this->t('no caching');
    $form['adv_varnish']['general']['page_cache_maximum_age'] = array(
      '#type' => 'select',
      '#title' => t('Page cache maximum age'),
      '#default_value' => $config->get('general.page_cache_maximum_age'),
      '#options' => $period,
      '#description' => t('The maximum time a page can be cached by varnish.'),
    );

    $form['adv_varnish']['connection'] = array(
      '#title' => t('Varnish Connection settings'),
      '#type' => 'details',
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );

    // This is replica from Varnish module.
    $form['adv_varnish']['connection']['control_terminal'] = array(
      '#type' => 'textfield',
      '#title' => t('Control Terminal'),
      '#default_value' => $config->get('connection.control_terminal'),
      '#required' => TRUE,
      '#description' => t('Set this to the server IP or hostname that varnish runs on (e.g. 127.0.0.1:6082). This must be configured for Drupal to talk to Varnish. Separate multiple servers with spaces.'),
    );

    // This is replica from Varnish module.
    $form['adv_varnish']['connection']['control_key'] = array(
      '#type' => 'textfield',
      '#title' => t('Control Key'),
      '#default_value' => $config->get('connection.control_key'),
      '#description' => t('Optional: if you have established a secret key for control terminal access, please put it here.'),
    );

    // This is replica from Varnish module.
    $form['adv_varnish']['connection']['socket_timeout'] = array(
      '#type' => 'textfield',
      '#title' => t('Connection timeout (milliseconds)'),
      '#default_value' => $config->get('connection.socket_timeout'),
      '#description' => t('If Varnish is running on a different server, you may need to increase this value.'),
      '#required' => TRUE,
    );

    // Availability settings.
    $form['adv_varnish']['available'] = array(
      '#title' => t('Availability settings'),
      '#type' => 'details',
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );

    $form['adv_varnish']['available']['exclude'] = array(
      '#title' => t('Excluded URLs'),
      '#type' => 'textarea',
      '#description' => t('Specify excluded request urls @format.', array('@format' => '<SERVER_NAME>|<partial REQUEST_URI *>')),
      '#default_value' => $config->get('available.exclude'),
    );

    $form['adv_varnish']['available']['https'] = array(
      '#title' => t('Enable for HTTPS pages'),
      '#type' => 'checkbox',
      '#description' => t('Check if you want enable Varnish support for HTTPS pages.'),
      '#default_value' => $config->get('available.https'),
    );

    $form['adv_varnish']['available']['admin_theme'] = array(
      '#title' => t('Enable for admin theme'),
      '#type' => 'checkbox',
      '#description' => t('Check if you want enable Varnish support for admin theme.'),
      '#default_value' => $config->get('available.admin_theme'),
    );

    $form['adv_varnish']['available']['authenticated_users'] = array(
      '#title' => t('Enable varnish for authenticated users'),
      '#type' => 'checkbox',
      '#description' => t('Check if you want enable Varnish support for authenticated users.'),
      '#default_value' => $config->get('available.authenticated_users'),
    );

    // Custom rules.
    $form['adv_varnish']['custom'] = array(
      '#title' => t('Custom Rules'),
      '#type' => 'details',
    );

    $form['adv_varnish']['custom']['rules'] = array(
      '#title' => t('Enabled Drupal Path'),
      '#type' => 'textarea',
      '#description' => t('Specify custom drupal path rules @format.', array('@format' => '<PATH>|<TTL>|<PAGE TAG>')),
      '#default_value' =>  $config->get('custom.rules'),
    );

    // User blocks settings.
    $form['adv_varnish']['userblocks'] = array(
      '#title' => t('User Blocks'),
      '#type' => 'details',
    );
    $form['adv_varnish']['userblocks']['clear_on_post'] = array(
      '#title' => t('Clear on every POST'),
      '#type' => 'checkbox',
      '#description' => t('It is common that each form submit made by user will affect it personnal data.'),
      '#default_value' => $config->get('userblocks.clear_on_post')
    );
    $period = array(3, 5, 10, 15, 30, 60, 120, 180, 240, 300, 600, 900, 1200,
      1800, 3600, 7200, 14400, 28800, 43200, 86400,
      172800, 259200, 345600, 604800
    );
    $period = array_map(array($this->dateFormatter, 'formatInterval'), array_combine($period, $period));
    $period[-1] = t('Pass through');
    ksort($period);
    $form['adv_varnish']['userblocks']['ttl'] = array(
      '#title' => t('Lifetime'),
      '#description' => t('For how long user blocks should be keept alive.'),
      '#type' => 'select',
      '#options' => $period,
      '#default_value' => !empty($config->get('userblocks.ttl')) ? $config->get('userblocks.ttl') : -1,
    );
    $form['adv_varnish']['userblocks']['cachetags'] = array(
      '#title' => t('Custom tags'),
      '#type' => 'textarea',
      '#description' => t('Custom cache tags, comma (,) separated, those should be backed by your code.'),
      '#default_value' => $config->get('userblocks.cachetags')
    );

    $form['adv_varnish']['cache_control'] = array(
      '#title' => t('Cache Control'),
      '#type' => 'details',
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );

    $form['adv_varnish']['cache_control']['anonymous'] = array(
      '#title' => t('Cache control headers for anonymous users.'),
      '#type' => 'textarea',
      '#description' => t('Cache control headers for anonymous users.'),
      '#default_value' => $config->get('cache_control.anonymous')
    );

    $form['adv_varnish']['cache_control']['logged'] = array(
      '#title' => t('Cache control headers for logged in users.'),
      '#type' => 'textarea',
      '#description' => t('Cache control headers for logged in users.'),
      '#default_value' => $config->get('cache_control.logged')
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValue('adv_varnish');
    $this->config('adv_varnish.settings')
      ->set('connection', $values['connection'])
      ->set('general', $values['general'])
      ->set('available', $values['available'])
      ->set('custom', $values['custom'])
      ->set('userblocks', $values['custom'])
      ->set('cache_control', $values['cache_control'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
