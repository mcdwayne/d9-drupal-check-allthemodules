<?php

/**
 * @file
 * Contains \Drupal\smart_ip_maxmind_geoip2_web_service\EventSubscriber\SmartIpEventSubscriber.
 */

namespace Drupal\smart_ip_maxmind_geoip2_web_service\EventSubscriber;

use Drupal\smart_ip_maxmind_geoip2_web_service\WebServiceUtility;
use Drupal\smart_ip_maxmind_geoip2_web_service\MaxmindGeoip2WebService;
use Drupal\smart_ip\GetLocationEvent;
use Drupal\smart_ip\AdminSettingsEvent;
use Drupal\smart_ip\DatabaseFileEvent;
use Drupal\smart_ip\SmartIpEventSubscriberBase;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Core functionalty of this Smart IP data source module.
 * Listens to Smart IP override events.
 *
 * @package Drupal\smart_ip_maxmind_geoip2_web_service\EventSubscriber
 */
class SmartIpEventSubscriber extends SmartIpEventSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public static function sourceId() {
    return 'maxmind_geoip2_web_service';
  }

  /**
   * {@inheritdoc}
   */
  public static function configName() {
    return 'smart_ip_maxmind_geoip2_web_service.settings';
  }

  /**
   * {@inheritdoc}
   */
  public function processQuery(GetLocationEvent $event) {
    if ($event->getDataSource() == self::sourceId()) {
      $location  = $event->getLocation();
      $ipAddress = $location->get('ipAddress');
      $record    = WebServiceUtility::getGeolocation($ipAddress);
      $lang      = \Drupal::languageManager()->getCurrentLanguage()->getId();
      if (!isset($record['country']['names'][$lang])) {
        // The current language is not yet supported by MaxMind, use English as
        // default language.
        $lang = 'en';
      }
      $country       = isset($record['country']['names'][$lang]) ? $record['country']['names'][$lang] : '';
      $countryCode   = isset($record['country']['iso_code']) ? $record['country']['iso_code'] : '';
      $region        = isset($record['subdivisions'][0]['names'][$lang]) ? $record['subdivisions'][0]['names'][$lang] : '';
      $regionCode    = isset($record['subdivisions'][0]['iso_code']) ? $record['subdivisions'][0]['iso_code'] : '';
      $city          = isset($record['city']['names'][$lang]) ? $record['city']['names'][$lang] : '';
      $zip           = isset($record['postal']['code']) ? $record['postal']['code'] : '';
      $latitude      = isset($record['location']['latitude']) ? $record['location']['latitude'] : '';
      $longitude     = isset($record['location']['longitude']) ? $record['location']['longitude'] : '';
      $timeZone      = isset($record['location']['time_zone']) ? $record['location']['time_zone'] : '';
      $isEuCountry   = isset($record['country']['is_in_european_union']) ? $record['country']['is_in_european_union'] : '';
      $isGdprCountry = isset($record['country']['isGdprCountry']) ? $record['country']['isGdprCountry'] : '';
      $location->set('originalData', $record)
        ->set('country', $country)
        ->set('countryCode', Unicode::strtoupper($countryCode))
        ->set('region', $region)
        ->set('regionCode', $regionCode)
        ->set('city', $city)
        ->set('zip', $zip)
        ->set('latitude', $latitude)
        ->set('longitude', $longitude)
        ->set('timeZone', $timeZone)
        ->set('isEuCountry', $isEuCountry)
        ->set('isGdprCountry', $isGdprCountry);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function formSettings(AdminSettingsEvent $event) {
    $config = \Drupal::config(self::configName());
    $form   = $event->getForm();
    $form['smart_ip_data_source_selection']['smart_ip_data_source']['#options'][self::sourceId()] = t(
      "Use @maxmind web service. A user ID and license key is required here. You 
      will need to @buy one of their services and they will provide you the 
      login details. You can view your user ID and license key inside your 
      @account.", [
        '@maxmind' => Link::fromTextAndUrl(t('MaxMind GeoIP2 Precision'), Url::fromUri('http://dev.maxmind.com/geoip/geoip2/web-services'))->toString(),
        '@buy'     => Link::fromTextAndUrl(t('buy'), Url::fromUri('https://www.maxmind.com/en/geoip2-precision-services'))->toString(),
        '@account' => Link::fromTextAndUrl(t('MaxMind account'), Url::fromUri('https://www.maxmind.com/en/my_license_key'))->toString(),
      ]);
    $form['smart_ip_data_source_selection']['maxmind_geoip2_web_service_type'] = [
      '#type'        => 'select',
      '#title'       => t('MaxMind GeoIP2 Precision Web Services'),
      '#description' => t('Choose type of service.'),
      '#options' => [
        MaxmindGeoip2WebService::COUNTRY_SERVICE  => t('Country'),
        MaxmindGeoip2WebService::CITY_SERVICE     => t('City'),
        MaxmindGeoip2WebService::INSIGHTS_SERVICE => t('Insights'),
      ],
      '#default_value' => $config->get('service_type'),
      '#states' => [
        'visible' => [
          ':input[name="smart_ip_data_source"]' => ['value' => self::sourceId()],
        ],
      ],
    ];
    $form['smart_ip_data_source_selection']['maxmind_geoip2_web_service_uid'] = [
      '#type'        => 'textfield',
      '#title'       => t('MaxMind GeoIP2 Precision user ID'),
      '#description' => t(
        "Enter your MaxMind GeoIP2 Precision account's user ID (view your user 
        ID @here).", [
          '@here' => Link::fromTextAndUrl(t('here'), Url::fromUri('https://www.maxmind.com/en/my_license_key'))->toString(),
        ]),
      '#default_value' => $config->get('user_id'),
      '#size' => 30,
      '#states' => [
        'visible' => [
          ':input[name="smart_ip_data_source"]' => ['value' => self::sourceId()],
        ],
      ],
    ];
    $form['smart_ip_data_source_selection']['maxmind_geoip2_web_service_license_key'] = [
      '#type'          => 'textfield',
      '#title'         => t('MaxMind GeoIP2 Precision license key'),
      '#default_value' => $config->get('license_key'),
      '#description'   => t(
        "Enter your MaxMind GeoIP2 Precision account's license key (view your 
        license key @here).", [
          '@here' => Link::fromTextAndUrl(t('here'), Url::fromUri('https://www.maxmind.com/en/my_license_key'))->toString(),
       ]),
      '#size' => 30,
      '#states' => [
        'visible' => [
          ':input[name="smart_ip_data_source"]' => ['value' => self::sourceId()],
        ],
      ],
    ];
    $event->setForm($form);
  }

  /**
   * {@inheritdoc}
   */
  public function validateFormSettings(AdminSettingsEvent $event) {
    /** @var \Drupal\Core\Form\FormStateInterface $formState */
    $formState  = $event->getFormState();
    if ($formState->getValue('smart_ip_data_source') == self::sourceId()) {
      if ($formState->isValueEmpty('maxmind_geoip2_web_service_uid')) {
        $formState->setErrorByName('maxmind_geoip2_web_service_uid', t('Please enter your Maxmind GeoIP2 Precision Web Services user ID.'));
      }
      if ($formState->isValueEmpty('maxmind_geoip2_web_service_license_key')) {
        $formState->setErrorByName('maxmind_geoip2_web_service_license_key', t('Please enter your Maxmind GeoIP2 Precision Web Services license key.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitFormSettings(AdminSettingsEvent $event) {
    /** @var \Drupal\Core\Form\FormStateInterface $formState */
    $formState = $event->getFormState();
    if ($formState->getValue('smart_ip_data_source') == self::sourceId()) {
      $config = \Drupal::configFactory()->getEditable(self::configName());
      $config->set('service_type', $formState->getValue('maxmind_geoip2_web_service_type'))
        ->set('user_id', $formState->getValue('maxmind_geoip2_web_service_uid'))
        ->set('license_key', $formState->getValue('maxmind_geoip2_web_service_license_key'))
        ->save();
    }
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
