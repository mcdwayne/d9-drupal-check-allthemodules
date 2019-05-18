<?php

namespace Drupal\ipquery\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ipquery\Ip2LocationDownloadService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configures ipquery settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The ipquery download service.
   *
   * @var \Drupal\ipquery\Ip2LocationDownloadService
   */
  protected $download;

  /**
   * SettingsForm constructor.
   *
   * @param \Drupal\ipquery\Ip2LocationDownloadService $download
   *   The ipquery download service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Ip2LocationDownloadService $download) {
    parent::__construct($config_factory);
    $this->download = $download;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('ipquery.ip2location.download')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ipquery.settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ipquery.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('ipquery.settings');

    $form['ip2location_edition'] = [
      '#type' => 'select',
      '#title' => $this->t('ip2location.com Edition'),
      '#options' => [
        'DB1' => 'DB1',
        'DB11' => 'DB11',
      ],
      '#default_value' => $config->get('ip2location_edition'),
      '#description' => $this->t('Select the edition (product) to download from ip2location.com.'),
      '#disabled' => $config->hasOverrides('ip2location_edition'),
    ];

    $form['ip2location_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('ip2location.com Token'),
      '#size' => 80,
      '#maxlength' => 255,
      '#default_value' => $config->get('ip2location_token'),
      '#description' => $this->t('Enter the license token to download data files from ip2location.com.'),
      '#disabled' => $config->hasOverrides('ip2location_token'),
    ];

    $form['debug_ip'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Debug IP'),
      '#default_value' => $config->get('debug_ip'),
      '#description' => $this->t('Check thix box to to accept IP in the URL query arguments (http://example.com?ip=1.2.3.4).'),
      '#disabled' => $config->hasOverrides('debug_ip'),
    ];

    $form['actions']['download'] = [
      '#type' => 'submit',
      '#value' => $this->t('Force download on next cron'),
      '#weight' => 10,
      '#submit' => ['::submitDownload'],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Save the token value.
    $this->config('ipquery.settings')
      ->set('ip2location_token', $form_state->getValue('ip2location_token'))
      ->set('ip2location_edition', $form_state->getValue('ip2location_edition'))
      ->set('debug_ip', $form_state->getValue('debug_ip'))
      ->save();
  }

  /**
   * Submit handler that save's and downloads.
   */
  public function submitDownload(array &$form, FormStateInterface $form_state) {
    // Submit the form to save the configuration.
    $this->submitForm($form, $form_state);

    // Clear download state.
    $this->download->setLast(0);
  }

}
