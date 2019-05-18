<?php

namespace Drupal\google_analytics_counter\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Drupal\google_analytics_counter\GoogleAnalyticsCounterAuthManagerInterface;
use Drupal\google_analytics_counter\GoogleAnalyticsCounterAppManagerInterface;
use Drupal\google_analytics_counter\GoogleAnalyticsCounterHelper;
use Drupal\google_analytics_counter\GoogleAnalyticsCounterMessageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class GoogleAnalyticsCounterSettingsForm.
 *
 * @package Drupal\google_analytics_counter\Form
 */
class GoogleAnalyticsCounterSettingsForm extends ConfigFormBase {

  /**
   * The state keyvalue collection.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Drupal\google_analytics_counter\GoogleAnalyticsCounterAuthManagerInterface.
   *
   * @var \Drupal\google_analytics_counter\GoogleAnalyticsCounterAuthManagerInterface
   */
  protected $authManager;

  /**
   * Drupal\google_analytics_counter\GoogleAnalyticsCounterAppManagerInterface.
   *
   * @var \Drupal\google_analytics_counter\GoogleAnalyticsCounterAppManagerInterface
   */
  protected $appManager;

  /**
   * The Google Analytics Counter message manager.
   *
   * @var \Drupal\google_analytics_counter\GoogleAnalyticsCounterMessageManagerInterface
   */
  protected $messageManager;

  /**
   * Constructs an instance of GoogleAnalyticsCounterSettingsForm.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state keyvalue collection to use.
   * @param \Drupal\google_analytics_counter\GoogleAnalyticsCounterAuthManagerInterface $auth_manager
   *   Google Analytics Counter Auth Manager object.
   * @param \Drupal\google_analytics_counter\GoogleAnalyticsCounterAppManagerInterface $app_manager
   *   Google Analytics Counter App Manager object.
   * @param \Drupal\google_analytics_counter\GoogleAnalyticsCounterMessageManagerInterface $message_manager
   *   Google Analytics Counter Message Manager object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, StateInterface $state, GoogleAnalyticsCounterAuthManagerInterface $auth_manager, GoogleAnalyticsCounterAppManagerInterface $app_manager, GoogleAnalyticsCounterMessageManagerInterface $message_manager) {
    parent::__construct($config_factory);
    $this->state = $state;
    $this->authManager = $auth_manager;
    $this->appManager = $app_manager;
    $this->messageManager = $message_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('state'),
      $container->get('google_analytics_counter.auth_manager'),
      $container->get('google_analytics_counter.app_manager'),
      $container->get('google_analytics_counter.message_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_analytics_counter_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['google_analytics_counter.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('google_analytics_counter.settings');

    $t_args = [
      ':href' => Url::fromUri('https://developers.google.com/analytics/devguides/reporting/core/v3/limits-quotas')->toString(),
      '@href' => 'Limits and Quotas on API Requests',
    ];
    $form['cron_interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum time to wait before fetching Google Analytics data (in minutes)'),
      '#default_value' => $config->get('general_settings.cron_interval'),
      '#min' => 0,
      '#max' => 10000,
      '#description' => $this->t('Google Analytics data is fetched and processed during cron. On the largest systems, cron may run every minute which could result in exceeding Google\'s quota policies. See <a href=:href target="_blank">@href</a> for more information. To bypass the minimum time to wait, set this value to 0.', $t_args),
      '#required' => TRUE,
    ];

    $form['chunk_to_fetch'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of items to fetch from Google Analytics in one request'),
      '#default_value' => $config->get('general_settings.chunk_to_fetch'),
      '#min' => 1,
      '#max' => 10000,
      '#description' => $this->t('The number of items to be fetched from Google Analytics in one request. The maximum allowed by Google is 10000. Default: 1000 items.'),
      '#required' => TRUE,
    ];

    $t_args = [
      ':href' => Url::fromUri('https://developers.google.com/analytics/devguides/reporting/core/v3/limits-quotas')->toString(),
      '@href' => 'Limits and Quotas on API Requests',
      ':href2' => $this->messageManager->googleProjectName(),
      '@href2' => 'Analytics API',
    ];
    $form['api_dayquota'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum GA API requests per day'),
      '#default_value' => $config->get('general_settings.api_dayquota'),
      '#min' => 1,
      '#max' => 50000,
      '#description' => $this->t('The Queries per day quota. Refer to <a href=:href2 target="_blank">@href2</a> page to view quotas for your Google app.', $t_args),
      '#required' => TRUE,
    ];

    $form['cache_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Google Analytics query cache (in hours)'),
      '#description' => $this->t('Limit the time in hours before getting fresh data with the same query to Google Analytics. Minimum: 0 hours. Maximum: 730 hours (approx. one month).'),
      '#default_value' => $config->get('general_settings.cache_length') / 3600,
      '#min' => 0,
      '#max' => 730,
      '#required' => TRUE,
    ];

    $get_count = GoogleAnalyticsCounterHelper::getCount('queue');
    $t_arg = [
      '%queue_count' => $get_count,
    ];
    $form['queue_time'] = [
      '#type' => 'number',
      '#title' => $this->t('Queue Time (in seconds)'),
      '#default_value' => $config->get('general_settings.queue_time'),
      '#min' => 1,
      '#max' => 10000,
      '#required' => TRUE,
      '#description' => $this->t('%queue_count items are in the queue. The number of items in the queue should be 0 after cron runs.', $t_arg) .
        '<br />' . $this->t('Having 0 items in the queue confirms that pageview counts are up to date. Increase Queue Time to process all the queued items during a single cron run. Default: 120 seconds.') .
        '<br /><strong>' . $this->t('Note: ') .'</strong>'. $this->t('Changing the Queue Time will require that the cache to be cleared, which may take a minute after submission.'),
    ];

    // Google Analytics start date settings.
    $form['start_date_details'] = [
      '#type' => 'details',
      '#title' => $this->t('Date range'),
      '#open' => TRUE,
    ];

    $start_date = [
      'custom' => $this->t('Custom'),
      'today' => $this->t('Today'),
      'yesterday' => $this->t('Yesterday'),
      '-1 week last sunday midnight' => $this->t('Last week'),
      'first day of previous month' => $this->t('Last month'),
      '7 days ago' => $this->t('Last 7 days'),
      '30 days ago' => $this->t('Last 30 days'),
      '3 months ago' => $this->t('Last 3 months'),
      '6 months ago' => $this->t('Last 6 months'),
      'first day of last year' => $this->t('Last year'),
      '14 November 2005' => $this->t('Since Launch'),
    ];

    $url = Url::fromUri('https://en.wikipedia.org/wiki/Google_Analytics', ['attributes' => ['target' => '_blank']]);
    $link = Link::fromTextAndUrl($this->t('Wikipedia'), $url)->toString();

    $form['start_date_details']['start_date'] = [
      '#type' => 'select',
      '#title' => $this->t('Date range'),
      '#description' => $this->t('Google launched the service in November 2005 after acquiring developer Urchin. ') . $link,
      '#default_value' => !empty($config->get('general_settings.start_date')) ? $config->get('general_settings.start_date') : '30 days ago',
      '#required' => TRUE,
      '#options' => $start_date,
    ];

    $form['start_date_details']['advanced_date'] = [
      '#type' => 'details',
      '#title' => $this->t('Custom dates'),
      '#description' => $this->t('Custom date fields are enabled if Date range is Custom.'),
      '#states' => [
        'open' => [
          ':input[name="start_date"]' => ['value' => 'custom'],
        ],
        'enabled' => [
          ':input[name="start_date"]' => ['value' => 'custom'],
        ],
      ],
    ];

    $form['start_date_details']['advanced_date']['custom_start_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Custom start date'),
      '#description' => $this->t('Set a custom start date for Google Analytics queries.'),
      '#default_value' => $config->get('general_settings.custom_start_date'),
      '#states' => [
        'required' => [
          ':input[name="start_date"]' => ['value' => 'custom'],
        ],
      ],
    ];

    $form['start_date_details']['advanced_date']['custom_end_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Custom end date'),
      '#description' => $this->t('Set a custom end date for Google Analytics queries.'),
      '#default_value' => $config->get('general_settings.custom_end_date'),
      '#states' => [
        'required' => [
          ':input[name="start_date"]' => ['value' => 'custom'],
        ],
      ],
    ];

    $web_properties = key($this->authManager->getWebPropertiesOptions());
    if ($web_properties === 'unauthenticated') {
      $this->messageManager->notAuthenticatedMessage();
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('google_analytics_counter.settings');

    $current_queue_time = $config->get('general_settings.queue_time');

    $values = $form_state->getValues();

    $end_date = $this->setEndDate($values);

    $config
      ->set('general_settings.cron_interval', $values['cron_interval'])
      ->set('general_settings.chunk_to_fetch', $values['chunk_to_fetch'])
      ->set('general_settings.api_dayquota', $values['api_dayquota'])
      ->set('general_settings.cache_length', $values['cache_length'] * 3600)
      ->set('general_settings.queue_time', $values['queue_time'])
      ->set('general_settings.start_date', $values['start_date'] == 'custom' ? '' : $values['start_date'])
      ->set('general_settings.end_date', $values['start_date'] == 'custom' ? '' : $end_date)
      ->set('general_settings.custom_start_date', $values['start_date'] == 'custom' ? $values['custom_start_date'] : '')
      ->set('general_settings.custom_end_date', $values['start_date'] == 'custom' ? $values['custom_end_date'] : '')
      ->save();

    // If the queue time has change the cache needs to be cleared.
    if ($current_queue_time != $values['queue_time']) {
      drupal_flush_all_caches();
      \Drupal::messenger()->addMessage(t(('Caches cleared.')));
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * Sets the end date into configuration.
   * @param array $values
   *
   * @return string
   */
  protected function setEndDate(array $values) {

    $end_date = '';
    switch ($values['start_date']) {
      case 'today':
        $end_date = 'today';
        break;

      case 'yesterday':
        $end_date = 'yesterday';
        break;

      case '-1 week last sunday midnight':
        $end_date = '-1 week next saturday';
        break;

      case 'first day of previous month':
        $end_date = 'last day of previous month';
        break;

      case '7 days ago':
        $end_date = '7 days ago +6 days';
        break;

      case '30 days ago':
        $end_date = '30 days ago +30 days -1 day';
        break;

      case '3 months ago':
        $end_date = '3 months ago +3 months -1 day';
        break;

      case '6 months ago':
        $end_date = '6 months ago +6 months - 1 day';
        break;

      case 'first day of last year':
        $end_date = 'last day of last year';
        break;

      default:
        break;
    }

    return $end_date;
  }

}
