<?php

namespace Drupal\advban\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\advban\AdvbanIpManagerInterface;

/**
 * Configure advban settings for this site.
 */
class AdvbanSettingsForm extends ConfigFormBase {

  /**
   * IP Manager variable.
   *
   * @var \Drupal\advban\AdvbanIpManagerInterface
   */
  protected $ipManager;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\advban\AdvbanIpManagerInterface $ip_manager
   *   Store AdvbanIpManagerInterface manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory,
    AdvbanIpManagerInterface $ip_manager) {
    $this->setConfigFactory($config_factory);
    $this->ipManager = $ip_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('advban.ip_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'advban_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'advban.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('advban.settings');

    $form['advban_expiry_durations'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Expiry durations'),
      '#default_value' => $config->get('expiry_durations'),
      '#description' => $this->t("Must be in a format that <a href='@strtotime'>PHP's strtotime function</a> can interpret.", [
        '@strtotime' => 'https://php.net/manual/function.strtotime.php',
      ]),
      '#required' => TRUE,
    ];

    $expiry_durations = $this->ipManager->expiryDurations();
    $default_expiry_duration = $config->get('default_expiry_duration');
    $expiry_durations_index = $this->ipManager->expiryDurationIndex($expiry_durations, $default_expiry_duration);

    $form['advban_default_expiry_duration'] = [
      '#title' => $this->t('Default IP ban expiry duration'),
      '#type' => 'select',
      '#options' => [ADVBAN_NEVER => $this->t('Never')] + $expiry_durations,
      '#default_value' => $expiry_durations_index,
      '#description' => $this->t('Select default expiration duration for ban.'),
    ];

    $form['advban_save_last_expiry_duration'] = [
      '#title' => $this->t('Save last IP ban expiry duration'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('save_last_expiry_duration'),
      '#description' => $this->t('Change default expiry duration after each IP ban.'),
    ];

    $form['advban_range_ip_format'] = [
      '#title' => $this->t('Range IP format'),
      '#type' => 'textfield',
      '#default_value' => $config->get('range_ip_format') ?: '@ip_start ... @ip_end',
      '#description' => $this->t('Range IP format for IP list. Use @ip_start, @ip_end variables.'),
    ];

    $form['advban_ban_text'] = [
      '#title' => $this->t('Ban text'),
      '#type' => 'textarea',
      '#default_value' => $config->get('advban_ban_text') ?: '@ip has been banned',
      '#description' => $this->t('Format ban text. Use @ip variable.'),
    ];

    $form['advban_ban_expire_text'] = [
      '#title' => $this->t('Ban text with expire'),
      '#type' => 'textarea',
      '#default_value' => $config->get('advban_ban_expire_text') ?: '@ip has been banned up to @expiry_date',
      '#description' => $this->t('Format ban text with expire date. Use @ip, @expiry_date variables.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Expiry durations validate.
    $arr_advban_expiry_durations = explode("\n", $form_state->getValue('advban_expiry_durations'));

    // Check for wrong time formats.
    foreach ($arr_advban_expiry_durations as $key => $duration) {
      if (!strtotime($duration)) {
        $form_state->setErrorByName('advban_expiry_durations', $this->t('Expiry time formats has wrong expiry time %duration.', ['%duration' => $duration]));
      }
      $arr_advban_expiry_durations[$key] = trim($arr_advban_expiry_durations[$key]);
    }

    // Check for expiry durations doubles.
    if (count($arr_advban_expiry_durations) != count(array_flip($arr_advban_expiry_durations))) {
      $form_state->setErrorByName('advban_expiry_durations', $this->t('Expiry durations has dublicated items'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('advban.settings')
      ->set('expiry_durations', $form_state->getValue('advban_expiry_durations'))
      ->set('default_expiry_duration', $this->ipManager->expiryDurations($form_state->getValue('advban_default_expiry_duration')))
      ->set('save_last_expiry_duration', $form_state->getValue('advban_save_last_expiry_duration'))
      ->set('range_ip_format', $form_state->getValue('advban_range_ip_format'))
      ->set('advban_ban_text', $form_state->getValue('advban_ban_text'))
      ->set('advban_ban_expire_text', $form_state->getValue('advban_ban_expire_text'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
