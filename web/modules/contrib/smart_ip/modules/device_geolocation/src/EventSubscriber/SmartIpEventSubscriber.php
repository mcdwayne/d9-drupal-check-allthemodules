<?php

/**
 * @file
 * Contains \Drupal\device_geolocation\EventSubscriber\SmartIpEventSubscriber.
 */

namespace Drupal\device_geolocation\EventSubscriber;

use Drupal\smart_ip\GetLocationEvent;
use Drupal\smart_ip\AdminSettingsEvent;
use Drupal\smart_ip\DatabaseFileEvent;
use Drupal\smart_ip\SmartIpEventSubscriberBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Core functionality of this Smart IP data source module.
 * Listens to Smart IP override events.
 *
 * @package Drupal\device_geolocation\EventSubscriber
 */
class SmartIpEventSubscriber extends SmartIpEventSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public static function sourceId() {
    return 'device_geolocation';
  }

  /**
   * {@inheritdoc}
   */
  public static function configName() {
    return 'device_geolocation.settings';
  }

  /**
   * {@inheritdoc}
   */
  public function formSettings(AdminSettingsEvent $event) {
    $config = \Drupal::config(self::configName());
    $form   = $event->getForm();
    $form['device_geolocation_preferences'] = [
      '#type'        => 'fieldset',
      '#title'       => t('Device Geolocation settings'),
      '#collapsible' => FALSE,
      '#collapsed'   => FALSE,
    ];
    $form['device_geolocation_preferences']['device_geolocation_use_ajax_check'] = [
      '#type'  => 'checkbox',
      '#title' => t(
        "Use AJAX in user's geolocation checking (useful if the site or pages 
        listed above are cached)"),
      '#default_value' => $config->get('use_ajax_check'),
    ];
    $frequencyCheck = $config->get('frequency_check');
    $form['device_geolocation_preferences']['device_geolocation_frequency_check'] = [
      '#title'       => t("Frequency of user's geolocation checking"),
      '#type'        => 'textfield',
      '#size'        => 10,
      '#description' => t(
        'Specify number of hours will prompt the user for geolocation. Leave it 
        empty to disable.'),
      '#default_value' => $frequencyCheck === NULL ? '' : $frequencyCheck / 3600,
      '#field_suffix'  => t('hours'),
    ];
    $form['device_geolocation_preferences']['device_geolocation_google_map_api_key'] = array(
      '#title'       => t('Google map API key'),
      '#type'        => 'textfield',
      '#description' => t(
        'The use of Google map service requires API key. Get your API key @here.', [
          '@here' => Link::fromTextAndUrl(t('here'), Url::fromUri('https://developers.google.com/maps/documentation/javascript/get-api-key'))->toString(),
        ]),
      '#default_value' => $config->get('google_map_api_key'),
    );
    $form['device_geolocation_preferences']['device_geolocation_google_map_region'] = [
      '#type' => 'textfield',
      '#title' => t('Google map region'),
      '#default_value' => $config->get('google_map_region'),
      '#description' => t(
        "Specify a region code, which alters the Google map service's behavior based 
        on a given country or territory. See @google_localization_region", [
          '@google_localization_region' => Link::fromTextAndUrl(t('Google Maps API - Localizing the Map (Region localization)'), Url::fromUri('https://developers.google.com/maps/documentation/javascript/localization#Region'))->toString(),
        ]),
    ];
    $form['device_geolocation_preferences']['device_geolocation_google_map_language'] = [
      '#type' => 'textfield',
      '#title' => t('Google map localization'),
      '#default_value' => $config->get('google_map_language'),
      '#description' => t(
        'Change the Google map service default language settings. 
        See @google_localization_language', [
          '@google_localization_language' => Link::fromTextAndUrl(t('Google Maps API - Localizing the Map (Language localization)'), Url::fromUri('https://developers.google.com/maps/documentation/javascript/localization#Language'))->toString(),
        ]),
    ];
    $event->setForm($form);
  }

  /**
   * {@inheritdoc}
   */
  public function validateFormSettings(AdminSettingsEvent $event) {
    /** @var \Drupal\Core\Form\FormStateInterface $formState */
    $formState = $event->getFormState();
    $frequencyCheck = $formState->getValue('device_geolocation_frequency_check');
    if (($frequencyCheck != '' && !is_numeric($frequencyCheck)) || $frequencyCheck < 0) {
      $formState->setErrorByName('device_geolocation_frequency_check', t("Frequency of user's geolocation checking must be a positive number."));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitFormSettings(AdminSettingsEvent $event) {
    $config = \Drupal::configFactory()->getEditable(self::configName());
    /** @var \Drupal\Core\Form\FormStateInterface $formState */
    $formState = $event->getFormState();
    $frequencyCheck = $formState->getValue('device_geolocation_frequency_check');
    if ($frequencyCheck == '') {
      $frequencyCheck = NULL;
    }
    else {
      // Convert from hours to seconds.
      $frequencyCheck = $frequencyCheck * 3600;
    }
    $config->set('use_ajax_check', $formState->getValue('device_geolocation_use_ajax_check'))
      ->set('frequency_check', $frequencyCheck)
      ->set('google_map_api_key', $formState->getValue('device_geolocation_google_map_api_key'))
      ->set('google_map_region', $formState->getValue('device_geolocation_google_map_region'))
      ->set('google_map_language', $formState->getValue('device_geolocation_google_map_language'))
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function processQuery(GetLocationEvent $event) {
  }

  /**
   * {@inheritdoc}
   */
  public function manualUpdate(DatabaseFileEvent $event) {
  }

  /**
   * {@inheritdoc}
   */
  public function cronRun(DatabaseFileEvent $event) {
  }

}
