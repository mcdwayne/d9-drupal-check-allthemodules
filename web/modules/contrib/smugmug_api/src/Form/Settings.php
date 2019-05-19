<?php

namespace Drupal\smugmug_api\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Datetime\DateFormatterInterface;

/**
 * Implements the SmugMug api Settings form.
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
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config Factory.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   Date Formatter.
   */
  public function __construct(ConfigFactoryInterface $config_factory,
                              DateFormatterInterface $date_formatter) {
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
    return 'instagram_api_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'smugmug_api.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('smugmug_api.settings');

    $form['client'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Client Settings'),
    ];

    $form['client']['help'] = [
      '#type' => '#markup',
      '#markup' => $this->t('To get your API Key, you need to register your application on @link.',
        [
          '@link' => Link::fromTextAndUrl('https://api.smugmug.com/api/developer/apply',
          Url::fromUri('https://api.smugmug.com/api/developer/apply'))->toString(),
        ]),
    ];

    $form['client']['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => $config->get('api_key'),
    ];

    $form['client']['api_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Secret'),
      '#default_value' => $config->get('api_secret'),
    ];

    $form['caching'] = [
      '#type' => 'details',
      '#title' => $this->t('SmugMug API Caching'),
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
    $this->config('smugmug_api.settings')
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('api_secret', $form_state->getValue('api_secret'))
      ->set('api_cache_maximum_age', $form_state->getValue('api_cache_maximum_age'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
