<?php

namespace Drupal\flickr_api\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements the Flickr API Settings form controller.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class Settings extends ConfigFormBase {


  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Settings constructor.
   *
   * @param \Drupal\flickr_api\Form\ConfigFactoryInterface $config_factory
   *   Config Factory.
   * @param \Drupal\flickr_api\Form\DateFormatterInterface $date_formatter
   *   Date Formatter.
   */
  public function __construct(ConfigFactoryInterface $config_factory, DateFormatterInterface $date_formatter) {
    parent::__construct($config_factory);

    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'flickr_api_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'flickr_api.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('flickr_api.settings');

    $form['credentials'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('OAuth Settings'),
    ];

    $form['credentials']['help'] = [
      '#type' => '#markup',
      '#markup' => $this->t('API Key from Flickr. Get an API Key at @link.',
        [
          '@link' => Link::fromTextAndUrl('https://www.flickr.com/services/apps/create/apply',
          Url::fromUri('https://www.flickr.com/services/apps/create/apply'))->toString(),
        ]
      ),
    ];

    $form['credentials']['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => $config->get('api_key'),
    ];

    $form['credentials']['api_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key secret'),
      '#default_value' => $config->get('api_secret'),
    ];

    $form['flickr_api'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Flickr API Settings'),
      '#description' => $this->t('The following settings connect Flickr API module with external APIs.'),
    ];

    $form['flickr_api']['host_uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Flickr URL'),
      '#default_value' => $config->get('host_uri'),
    ];

    $form['flickr_api']['api_uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Flickr API URL'),
      '#default_value' => $config->get('api_uri'),
    ];

    $form['caching'] = [
      '#type' => 'details',
      '#title' => $this->t('Flickr API Caching'),
      '#open' => TRUE,
      '#description' => $this->t('API caching is recommended for all websites.'),
    ];

    // Identical options to the ones for block caching.
    // @see \Drupal\Core\Block\BlockBase::buildConfigurationForm()
    $period = [
      0,
      60,
      180,
      300,
      600,
      900,
      1800,
      2700,
      3600,
      10800,
      21600,
      32400,
      43200,
      86400,
    ];

    $period = array_map([$this->dateFormatter, 'formatInterval'], array_combine($period, $period));
    $period[0] = '<' . $this->t('no caching') . '>';
    $form['caching']['api_cache_maximum_age'] = [
      '#type' => 'select',
      '#title' => $this->t('API cache maximum age'),
      '#default_value' => $config->get('api_cache_maximum_age'),
      '#options' => $period,
      '#description' => $this->t('The maximum time a API request can be cached by Drupal.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('flickr_api.settings')
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('api_secret', $form_state->getValue('api_secret'))
      ->set('host_uri', $form_state->getValue('host_uri'))
      ->set('api_uri', $form_state->getValue('api_uri'))
      ->set('api_cache_maximum_age', $form_state->getValue('api_cache_maximum_age'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
