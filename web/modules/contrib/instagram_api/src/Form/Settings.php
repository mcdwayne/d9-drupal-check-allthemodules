<?php

namespace Drupal\instagram_api\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Datetime\DateFormatterInterface;

/**
 * Implements the Instagram api Settings form.
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
      'instagram_api.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('instagram_api.settings');

    $form['client'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Client Settings'),
    ];

    $form['client']['help'] = [
      '#type' => '#markup',
      '#markup' => $this->t('To get your Client ID, you need to register your application on @link.',
        [
          '@link' => Link::fromTextAndUrl('https://www.instagram.com/developer/clients/manage/',
          Url::fromUri('https://www.instagram.com/developer/clients/manage/'))->toString(),
        ]),
    ];

    $form['client']['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client ID'),
      '#default_value' => $config->get('client_id'),
    ];

    $form['client']['client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client Secret'),
      '#default_value' => $config->get('client_secret'),
    ];

    if ($config->get('client_id') != '' && $config->get('client_secret') != '') {
      $options = ['attributes' => ['target' => '_blank']];

      $form['client']['access_token'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Access Token'),
        '#default_value' => $config->get('access_token'),
        '#description' => $this->t('To get your Access Token, @link.',
          [
            '@link' => Link::fromTextAndUrl('click here', Url::fromUri($this->accessUrl(), $options))->toString(),
          ]),
      ];
    }

    $form['caching'] = [
      '#type' => 'details',
      '#title' => $this->t('Instagram API Caching'),
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
    $this->config('instagram_api.settings')
      ->set('client_id', $form_state->getValue('client_id'))
      ->set('client_secret', $form_state->getValue('client_secret'))
      ->set('access_token', $form_state->getValue('access_token'))
      ->set('api_cache_maximum_age', $form_state->getValue('api_cache_maximum_age'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Generate the Access Url.
   *
   * @return string
   *   URL.
   */
  private function accessUrl() {
    $config = $this->config('instagram_api.settings');
    $redirectUrl = Url::fromUri('internal:/instagram_api/callback', ['absolute' => TRUE])->toString();
    $urlBase = $config->get('api_uri') . 'authorize';

    $query = [
      'client_id' => $config->get('client_id'),
      'response_type' => 'code',
      'redirect_uri' => $redirectUrl,
    ];

    $url = Url::fromUri($urlBase, [
      'query' => $query,
      'absolute' => TRUE,
    ])->toString();

    return $url;
  }

}
