<?php

namespace Drupal\grnhse\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class GrnhseConfig.
 *
 * @package Drupal\grnhse\Form
 */
class GrnhseConfig extends ConfigFormBase {


  /**
   * GrnhseConfig constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'grnhse_config';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'grnhse.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('grnhse.settings');

    $form['endpoint'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('API details'),
    ];

    $form['endpoint']['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => $config->get('api_key'),
      '#description' => $this->t('The API Key configured in Greenhouse.'),
      '#required' => TRUE,
    ];

    $form['endpoint']['board_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Board Token'),
      '#default_value' => $config->get('board_token'),
      '#description' => $this->t('The Board Token configured in Greenhouse.'),
      '#required' => TRUE,
    ];

    $form['cron'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Cron Settings'),
    ];

    $form['cron']['sync_interval'] = [
      '#type' => 'select',
      '#title' => $this->t('Asset refresh interval'),
      '#options' => [
        '-1' => 'Every cron run',
        '3600' => 'Every hour',
        '7200' => 'Every 2 hours',
        '10800' => 'Every 3 hours',
        '14400' => 'Every 4 hours',
        '21600' => 'Every 6 hours',
        '28800' => 'Every 8 hours',
        '43200' => 'Every 12 hours',
        '86400' => 'Daily',
        '0' => 'Never',
      ],
      '#default_value' => empty($config->get('sync_interval')) ? 86400 : $config->get('sync_interval'),
      '#description' => $this->t('How often should data saved in this site be synced with Greenhouse?'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('grnhse.settings')
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('board_token', $form_state->getValue('board_token'))
      ->set('sync_interval', $form_state->getValue('sync_interval'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Gets a form value from stored config.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   * @param string $field_name
   *   The key of the field in the simple config.
   *
   * @return mixed
   *   The value for the given form field, or NULL.
   */
  protected function getFormValueFromConfig(FormStateInterface $form_state, $field_name) {
    $config_name = $this->getEditableConfigNames();
    $value = $this->config(reset($config_name))->get($field_name);
    return $value;
  }

  /**
   * Gets a form field value, either from the form or from config.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   * @param string $field_name
   *   The key of the field in config. (This may differ from form field key).
   *
   * @return mixed
   *   The value for the given form field, or NULL.
   */
  protected function getFieldValue(FormStateInterface $form_state, $field_name) {
    // If the user has entered a value use it, if not check config.
    $value = $form_state->getValue($field_name) ? null : $this->getFormValueFromConfig($form_state, $field_name);
    return $value;
  }

}
