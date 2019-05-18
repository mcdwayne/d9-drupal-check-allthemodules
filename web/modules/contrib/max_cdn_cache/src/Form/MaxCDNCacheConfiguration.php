<?php

namespace Drupal\max_cdn_cache\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Create form to save API and clear cache.
 */
class MaxCDNCacheConfiguration extends ConfigFormBase {

  protected $maxCDNService;

  /**
   * {@inheritdoc}
   */
  public function __construct($maxCDNService) {
    $this->maxCDNService = $maxCDNService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('max_cdn_cache_maxcdn_service'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'max_cdn_cache_configuration';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['max_cdn_cache.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Getting the configuration value
    $default_value = $this->config('max_cdn_cache.settings');

    $form['max_cdn_cache_config'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('MaxCDN Configuration'),
      '#weight' => 5,
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $form['max_cdn_cache_config']['max_cdn_cache_alias'] = [
      '#type' => 'textfield',
      '#size' => 170,
      '#maxlength' => 255,
      '#default_value' => $default_value->get('max_cdn_cache_alias'),
      '#required' => FALSE,
      '#title' => $this->t('Company Alias'),
    ];
    $form['max_cdn_cache_config']['max_cdn_cache_consumer_key'] = [
      '#type' => 'textfield',
      '#size' => 170,
      '#maxlength' => 255,
      '#default_value' => $default_value->get('max_cdn_cache_consumer_key'),
      '#required' => FALSE,
      '#title' => $this->t('Consumer Key'),
    ];
    $form['max_cdn_cache_config']['max_cdn_cache_consumer_secret'] = [
      '#type' => 'textfield',
      '#size' => 170,
      '#maxlength' => 255,
      '#default_value' => $default_value->get('max_cdn_cache_consumer_secret'),
      '#required' => FALSE,
      '#title' => $this->t('Consumer Secret'),
    ];
    $form['max_cdn_cache_config']['saveconfig'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Configuration'),
    ];
    $form['max_cdn_cache_config_clear'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('MaxCDN Clear cache'),
      '#weight' => 6,
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $form['max_cdn_cache_config_clear']['max_cdn_cache_zoneid'] = [
      '#type' => 'select',
      '#required' => FALSE,
      '#title' => $this->t('Select Zone'),
      '#options' => $this->maxCDNService->getZoneList(),
    ];
    $form['max_cdn_cache_config_clear']['clearcache'] = [
      '#type' => 'submit',
      '#value' => $this->t('Clear cache'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    if ($form_state->getTriggeringElement()['#parents'][0] === 'clearcache') {
      $zoneid = $form_state->getValue('max_cdn_cache_zoneid');
      if (empty($zoneid)) {
        $form_state->setErrorByName("max_cdn_cache_zoneid", t("Please select ZoneID."));
      }
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    if ($form_state->getTriggeringElement()['#parents'][0] === 'clearcache') {
      $zoneID = $form_state->getValue('max_cdn_cache_zoneid');
      if (!empty($zoneID)) {
        $this->maxCDNService->deleteZone($zoneID);
      }
    }
    if ($form_state->getTriggeringElement()['#parents'][0] === 'saveconfig') {
      $config = $this->config('max_cdn_cache.settings');
      $max_cdn_cache_alias = $form_state->getValue('max_cdn_cache_alias');
      $max_cdn_cache_consumer_key = $form_state->getValue('max_cdn_cache_consumer_key');
      $max_cdn_cache_consumer_secret = $form_state->getValue('max_cdn_cache_consumer_secret');

      $config->set('max_cdn_cache_alias', $max_cdn_cache_alias);
      $config->set('max_cdn_cache_consumer_key', $max_cdn_cache_consumer_key);
      $config->set('max_cdn_cache_consumer_secret', $max_cdn_cache_consumer_secret);

      $config->save();
    }

   if (method_exists($this, '_submitForm')) {
     $this->_submitForm($form, $form_state);
   }

   parent::submitForm($form, $form_state);
  }

}
