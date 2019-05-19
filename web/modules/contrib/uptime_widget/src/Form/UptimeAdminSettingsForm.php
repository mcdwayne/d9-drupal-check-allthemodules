<?php

namespace Drupal\uptime_widget\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\uptime_widget\Service\UptimeWidgetService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * {@inheritdoc}
 */
class UptimeAdminSettingsForm extends ConfigFormBase {

  /**
   * The uptime_widget service object.
   *
   * @var \Drupal\uptime_widget\Service\UptimeWidgetService
   */
  private $uptimeWidgetService;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The currently active request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Constructs a new UptimeAdminSettingsForm.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\uptime_widget\Service\UptimeWidgetService $uptime_widget_service
   *   The uptime_widget service object.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The currently active request object.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    UptimeWidgetService $uptime_widget_service,
    StateInterface $state,
    RequestStack $request_stack
  ) {
    parent::__construct($config_factory);
    $this->uptimeWidgetService = $uptime_widget_service;
    $this->state = $state;
    $this->request = $request_stack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('uptime_widget'),
      $container->get('state'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uptime_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['uptime_widget.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('uptime_widget.settings');
    // Essential to have some credentials.
    $api_key = $config->get('api_key');
    $monitor_id = reset($config->get('monitor_ids')) ?: $config->get('monitor_id');
    // Where to find the all-time uptime ratio.
    $url = "http://api.uptimerobot.com/getMonitors?apiKey=" . $api_key . "&monitors=" . $monitor_id . "&format=xml";

    $monitor_data = $this->uptimeWidgetService->sendPost('getMonitors', [
      'monitors' => $monitor_id,
    ]);
    if ($monitor_data['stat'] == 'ok') {
      $enabled = $monitor_data['monitors'][0]['status'] != 0 ? 1 : 0;
      $monitoring_interval = $monitor_data['monitors'][0]['interval'] / 60;
      // Calculate monitoring interval in seconds.
      if ($monitoring_interval > 120) {
        // After 120 minutes we show 3 hours. So current value in hours.
        $monitoring_interval = $monitoring_interval / 60 + 118;
      }
    }
    else {
      $enabled = 0;
      $monitoring_interval = 5;
    }

    $form['api_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Uptime'),
    ];

    $form['api_settings']['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $enabled,
      '#description' => $this->t('Disabling pauses the monitor until re-enabling and removes the ratio display. Disable uptime if you only want to use the copyright notice or when your site might be down temporarily for example during development.'),
    ];

    $form['api_settings']['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#default_value' => $api_key,
      '#description' => $this->t('To get an API key go to @api_key -> Sign-up (free) -> Account Activation -> Login -> Add New Monitor -> Monitor Type: HTTP(s) -> Create Monitor -> My Settings  -> Create the main API key  -> Copy & paste it here.', [
        '@api_key' => Link::fromTextAndUrl($this->t('https://uptimerobot.com'), Url::fromUri('https://uptimerobot.com/', [
          'attributes' => ['target' => '_blank'],
        ]))->toString(),
      ]),
      '#size' => 40,
      '#maxlength' => 40,
      '#states' => [
        'required' => [
          [':input[name="enabled"]' => ['checked' => TRUE]],
          'or',
          [':input[name="api_key"]' => ['empty' => FALSE]],
          'or',
          [':input[name="monitor_id"]' => ['empty' => FALSE]],
        ],
      ],
    ];

    $form['api_settings']['monitor_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Monitor ID'),
      '#default_value' => $monitor_id,
      '#description' => $this->t('To find your Monitor ID go to @monitor_id change the last part of the url, reload and copy your monitor ID to here', [
        '@monitor_id' => Link::fromTextAndUrl($this->t('api.uptimerobot.com/getMonitors?apiKey=FILL-IN-YOUR-API-KEY-HERE'), Url::fromUri('http://api.uptimerobot.com/getMonitors?apiKey=FILL-IN-YOUR-API-KEY-HERE', [
          'attributes' => ['target' => '_blank'],
        ]))->toString(),
      ]),
      '#size' => 10,
      '#maxlength' => 10,
      '#states' => [
        'required' => [
          [':input[name="enabled"]' => ['checked' => TRUE]],
          'or',
          [':input[name="api_key"]' => ['empty' => FALSE]],
          'or',
          [':input[name="monitor_id"]' => ['empty' => FALSE]],
        ],
      ],
    ];

    $form['api_settings']['ratio_decimal_separator'] = [
      '#type' => 'select',
      '#title' => $this->t('Decimal separator'),
      '#options' => [
        '.' => $this->t('Decimal point'),
        ',' => $this->t('Decimal comma'),
      ],
      '#default_value' => $config->get('ratio_decimal_separator'),
      '#description' => $this->t('The decimal separator is a symbol used to mark the border between the integral and the fractional parts of a decimal numeral.'),
      '#required' => TRUE,
    ];

    $form['api_settings']['ratio_scale'] = [
      '#type' => 'number',
      '#title' => $this->t('Scale'),
      '#min' => 0,
      '#max' => 3,
      '#default_value' => $config->get('ratio_scale'),
      '#description' => $this->t('The number of digits to the right of the decimal. Allowed range 0-3.'),
      '#required' => TRUE,
    ];

    $form['api_settings']['nocss'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('No CSS'),
      '#default_value' => $config->get('nocss'),
      '#description' => $this->t("Do not apply the module's supplied CSS."),
    ];

    $form['api_settings']['monitoring_interval'] = [
      '#type' => 'range',
      '#title' => $this->t('Monitoring interval'),
      '#min' => 5,
      '#max' => 142,
      '#step' => 1,
      '#default_value' => $monitoring_interval,
      '#description' => $this->t('The interval for the monitoring check by UptimeRobot (5 minutes by default). Allowed range 5 minutes - 24 hours.'),
      '#required' => TRUE,
      '#theme' => 'uptime_range',
    ];

    // Grabbing the uptime ratio once a day is good enough, but leave it up to
    // the site owner to decide. Second option is the actual set cron interval.
    $last_refresh = $this->state->get('uptime_widget.next_execution') - $config->get('refresh_interval');
    if ($last_refresh <= 0) {
      $interval_ago = $this->t('never');
    }
    else {
      $interval_ago = \Drupal::service('date.formatter')
        ->formatInterval((\Drupal::time()->getRequestTime() - $last_refresh));
      $interval_ago = $this->t('@interval_ago ago', [
        '@interval_ago' => $interval_ago,
      ]);
    }
    $form['api_settings']['refresh_interval'] = [
      '#type' => 'radios',
      '#title' => $this->t('Refresh interval'),
      '#options' => [
        86400 => $this->t('24 hours (recommended)'),
        0 => $this->t('Every cron run'),
      ],
      '#default_value' => $config->get('refresh_interval'),
      '#description' => $this->t('Saving this form refreshes the uptime ratio instantly, independent from this setting. Last refresh was @interval.', ['@interval' => $interval_ago]),
      '#required' => TRUE,
    ];

    // Offering the possibility to check the source of the data.
    $api_settings_raw_check_description = $this->t('Once you saved your credentials, you can check the raw data at');
    $api_settings_raw_check_description .= ':<br />';
    $api_settings_raw_check_description .= Link::fromTextAndUrl($url, Url::fromUri($url, [
      'attributes' => ['target' => '_blank'],
    ]))->toString();
    $api_settings_raw_check_description .= '<br />' . $this->t('The current Uptime ratio is @ratio %.', ['@ratio' => $this->state->get('uptime_widget.ratio', 0)]);
    $form['api_settings']['raw check'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Data check'),
      '#description' => $api_settings_raw_check_description,
    ];

    $form['notice'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Copyright notice'),
    ];

    $form['notice']['notice_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $config->get('notice_enabled'),
    ];

    // Set the default option as stored.
    $default = $config->get('url_name');
    if (!isset($default) || empty($default)) {
      $config->set('url_name', $this->request->getHost())->save();
    }
    // For the examples we use real data.
    // Current domain name without the leading protocol.
    $host = $this->request->getHost();
    // Force $default to be a valid option if not equal to one of the three.
    if ($default != $host && $default != $this->config('system.site')->get('name') && $default != ' ') {
      $default = $host;
    }
    $year = $config->get('year');
    $notice = $config->get('prepend') . ' ©' . (($year != date('Y') && !empty($year)) ? $year . '-' . date('Y') : date('Y'));
    $form['notice']['url_name'] = [
      // Create different types of notices to choose from.
      '#type' => 'radios',
      '#title' => $this->t('Choose a notice'),
      '#options' => [
        $host => '<strong>' . $notice . ' ' . $host . '</strong> ' . $this->t('(Base url. Default.)'),
        $this->config('system.site')->get('name') => '<strong>' . $notice . ' ' . $this->config('system.site')->get('name') . '</strong> ' . $this->t("(Site name. Preferable if the site name is a person's full name or a company name.)"),
        ' ' => '<strong>' . $notice . '</strong> ' . $this->t('(Leaving out the designation of owner is not recommended.)'),
      ],
      '#default_value' => $default,
      '#description' => $this->t("'Year of first publication' is not used until entered below, for example © 2009-") . date('Y') . '. ' . t('Save this form to refresh above examples.'),
    ];

    $notice_year_description = $this->t("Leave empty to display only the current year (default). Also if the 'starting year' equals the 'current year' only one will be displayed until next year.");
    $notice_year_description .= '<br />' . $this->t("To play safe legally, it's best to enter a 'Year of first publication', although copyright is in force even without any notice.");
    $form['notice']['year'] = [
      '#type' => 'textfield',
      '#title' => $this->t('What year was the domain first online?'),
      '#default_value' => $year,
      '#description' => $notice_year_description,
      '#size' => 4,
      '#maxlength' => 4,
    ];

    $form['notice']['prepend'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Prepend text'),
      '#default_value' => trim($config->get('prepend')),
      '#description' => $this->t("For example 'All images' on a photographer's website."),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue(['api_key']) && $form_state->getValue(['monitor_id'])) {
      $monitor_id = $form_state->getValue(['monitor_id']);
      if (!is_numeric($monitor_id)) {
        $form_state->setErrorByName('monitor_id', $this->t('Monitor ID should be a number.'));
      }

      // Check API key and existing monitor to change it settings on submit.
      $data = $this->uptimeWidgetService->sendPost('getMonitors', [
        'api_key' => $form_state->getValue('api_key'),
        'monitors' => $form_state->getValue('monitor_id'),
      ]);
      if (isset($data['error']['type'])) {
        if (isset($data['error']['parameter_name']) && $data['error']['type'] == 'invalid_parameter' && $data['error']['parameter_name'] == 'api_key') {
          $form_state->setErrorByName('api_key', $this->t('Invalid API Key.'));
        }
        if ($data['error']['type'] == 'not_found') {
          $form_state->setErrorByName('monitor_id', $this->t('Invalid Monitor ID.'));
        }
      }
    }

    // Before 1991 there was no world wide web and the future can't be a
    // 'year of first publication' but it can be left empty.
    $year = $form_state->getValue('year');
    if ((!is_numeric($year) || $year < 1991 || $year > date('Y')) && !empty($year)) {
      $form_state->setErrorByName('year', $this->t('Invalid year.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $enabled = $form_state->getValue('enabled');
    $monitor_id = $form_state->getValue('monitor_id');
    $monitoring_interval = $form_state->getValue('monitoring_interval');

    $psp_url = $this->createPublicStatusPage($monitor_id);

    /** @var \Drupal\Core\Config\Config $config */
    $config = $this->configFactory()->getEditable('uptime_widget.settings');
    $config->set('api_key', trim($form_state->getValue('api_key')))
      ->set('monitor_ids', [trim($monitor_id)])
      ->set('ratio_scale', $form_state->getValue('ratio_scale'))
      ->set('ratio_decimal_separator', $form_state->getValue('ratio_decimal_separator'))
      ->set('nocss', $form_state->getValue('nocss'))
      ->set('refresh_interval', $form_state->getValue('refresh_interval'))
      ->set('notice_enabled', $form_state->getValue('notice_enabled'))
      ->set('year', $form_state->getValue('year'))
      ->set('prepend', $form_state->getValue('prepend'))
      ->set('url_name', $form_state->getValue('url_name'))
      ->save();

    // Calculate monitoring interval in seconds.
    if ($monitoring_interval > 120) {
      // After 120 minutes we show 3 hours. So current value in days.
      $monitoring_interval = ($monitoring_interval - 118) * 3600;
    }
    else {
      // Current value in minutes.
      $monitoring_interval *= 60;
    }

    // Pause or activate monitoring depending on the 'enabled' checkbox.
    $this->uptimeWidgetService->sendPost('editMonitor', [
      'id' => $monitor_id,
      'interval' => $monitoring_interval,
      'status' => $enabled,
    ]);

    $monitors[$monitor_id]['status'] = $enabled;
    $this->state->set('uptime_widget.monitors', $monitors);

    if ($form_state->getValue('create_psp')) {
      $psp_url = $this->createPublicStatusPage($monitor_id);
      $config->set('psp_url', $psp_url)->save();
    }

    $this->uptimeWidgetService->fetchAccountDetails();
    // To find a cron call here looks odd but it is the only way to have any
    // changed variables in the form being processed in the hook_cron(). After
    // submitting the form you come back on the same form and that is when all
    // new variables are available.
    // Execution time has to be reset to force an instant cron run.
    $this->state->set('uptime_widget.next_execution', 0);
    \Drupal::service('cron')->run();

    if ($enabled) {
      drupal_set_message($this->t('The configuration options have been saved. The monitor is running.'));
    }
    else {
      drupal_set_message($this->t('The configuration options have been saved. The monitor is paused.'));
    }
  }

  /**
   * Create Public Status Page.
   *
   * @param $monitor_id
   *   Monitor id to create PSP.
   *
   * @return string
   *   Public status page Url.
   */
  protected function createPublicStatusPage($monitor_id) {
    $psps = $this->uptimeWidgetService->sendPost('getPSPs');

    // Search PSP for existing monitor and return it url.
    if (!empty($psps['stat']) && $psps['stat'] == 'ok') {
      foreach ($psps['psps'] as $psp) {
        if (count($psp['monitors']) == 1 && $psp['monitors'][0] == $monitor_id) {
          return $psp['standard_url'];
        }
      }
    }

    // Otherwise will be created a new PSP for one monitor.
    $result = $this->uptimeWidgetService->sendPost('newPSP', [
      'type' => 1,
      'friendly_name' => $this->config('system.site')->get('name'),
      'monitors' => $monitor_id,
    ]);
    if (!empty($result['stat']) && $result['stat'] == 'ok' && !empty($result['psp']['standard_url'])) {
      return $result['psp']['standard_url'];
    }
    else {
      return '';
    }
  }

}
