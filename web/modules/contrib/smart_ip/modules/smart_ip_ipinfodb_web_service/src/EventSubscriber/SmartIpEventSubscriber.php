<?php

/**
 * @file
 * Contains \Drupal\smart_ip_ipinfodb_web_service\EventSubscriber\SmartIpEventSubscriber.
 */

namespace Drupal\smart_ip_ipinfodb_web_service\EventSubscriber;

use Drupal\smart_ip_ipinfodb_web_service\WebServiceUtility;
use Drupal\smart_ip\GetLocationEvent;
use Drupal\smart_ip\AdminSettingsEvent;
use Drupal\smart_ip\DatabaseFileEvent;
use Drupal\smart_ip\SmartIpEventSubscriberBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Component\Utility\Unicode;

/**
 * Core functionalty of this Smart IP data source module.
 * Listens to Smart IP override events.
 *
 * @package Drupal\smart_ip_ipinfodb_web_service\EventSubscriber
 */
class SmartIpEventSubscriber extends SmartIpEventSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public static function sourceId() {
    return 'ipinfodb_web_service';
  }

  /**
   * {@inheritdoc}
   */
  public static function configName() {
    return 'smart_ip_ipinfodb_web_service.settings';
  }

  /**
   * {@inheritdoc}
   */
  public function processQuery(GetLocationEvent $event) {
    if ($event->getDataSource() == self::sourceId()) {
      $location = $event->getLocation();
      $ipAddress = $location->get('ipAddress');
      $record = WebServiceUtility::getGeolocation($ipAddress);
      $config = \Drupal::config(self::configName());
      $version = $config->get('version');
      foreach ($record as &$item) {
        if (strpos($item, 'Please upgrade') !== FALSE || strpos($item, 'Invalid IP address') !== FALSE || $item == '-') {
          // Make the value "This parameter is unavailable in selected .BIN
          // data file. Please upgrade." or "Invalid IP address" or "-" as
          // NULL.
          $item = NULL;
        }
      }
      if ($version == 2) {
        $region = '';
        if (isset($record['RegionCode']) && isset($record['CountryCode'])) {
          $regionResult = smart_ip_get_region_static($record['CountryCode'], $record['RegionCode']);
          $region = $regionResult[$record['CountryCode']][$record['RegionCode']];
        }
        elseif (isset($record['RegionName'])) {
          $region = $record['RegionName'];
        }
        $country       = isset($record['CountryName']) ? $record['CountryName'] : '';
        $countryCode   = isset($record['CountryCode']) ? $record['CountryCode'] : '';
        $regionCode    = isset($record['RegionCode']) ? $record['RegionCode'] : '';
        $city          = isset($record['City']) ? $record['City'] : '';
        $zip           = isset($record['ZipPostalCode']) ? $record['ZipPostalCode'] : '';
        $latitude      = isset($record['Latitude']) ? $record['Latitude'] : '';
        $longitude     = isset($record['Longitude']) ? $record['Longitude'] : '';
        $timeZone      = isset($record['TimeZone']) ? $record['TimeZone'] : '';
        $isEuCountry   = isset($record['isEuCountry']) ? $record['isEuCountry'] : '';
        $isGdprCountry = isset($record['isGdprCountry']) ? $record['isGdprCountry'] : '';
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
      elseif ($version == 3) {
        $country       = isset($record['countryName']) ? $record['countryName'] : '';
        $countryCode   = isset($record['countryCode']) ? $record['countryCode'] : '';
        $region        = isset($record['regionName']) ? $record['regionName'] : '';
        $regionCode    = isset($record['regionCode']) ? $record['regionCode'] : '';
        $city          = isset($record['cityName']) ? $record['cityName'] : '';
        $zip           = isset($record['zipCode']) ? $record['zipCode'] : '';
        $latitude      = isset($record['latitude']) ? $record['latitude'] : '';
        $longitude     = isset($record['longitude']) ? $record['longitude'] : '';
        $timeZone      = isset($record['timeZone']) ? $record['timeZone'] : '';
        $isEuCountry   = isset($record['isEuCountry']) ? $record['isEuCountry'] : '';
        $isGdprCountry = isset($record['isGdprCountry']) ? $record['isGdprCountry'] : '';
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
  }

  /**
   * {@inheritdoc}
   */
  public function formSettings(AdminSettingsEvent $event) {
    $config = \Drupal::config(self::configName());
    $form   = $event->getForm();
    $form['smart_ip_data_source_selection']['smart_ip_data_source']['#options'][self::sourceId()] = t(
      "Use @ipinfodb web service. The @ip2location free version database is used 
      by @ipinfodb in their web service. You will need an API key to use this 
      and you must be @login to get it. Note: if @ipinfodb respond too slow to 
      geolocation request, your site's performance will be affected specially if 
      Smart IP is configured to geolocate anonymous users.", [
        '@ipinfodb'    => Link::fromTextAndUrl(t('IPInfoDB.com'), Url::fromUri('http://www.ipinfodb.com'))->toString(),
        '@ip2location' => Link::fromTextAndUrl(t('IP2Location'), Url::fromUri('http://www.ip2location.com'))->toString(),
        '@login'       => Link::fromTextAndUrl(t('logged in'), Url::fromUri('http://ipinfodb.com/login.php'))->toString(),
      ]);
    $form['smart_ip_data_source_selection']['ipinfodb_api_version'] = [
      '#type'          => 'select',
      '#title'         => t('IPInfoDB API version'),
      '#default_value' => $config->get('version'),
      '#options'       => [2 => 2, 3 => 3],
      '#description'   => t('IPInfoDB.com version 2 do have region code, in version 3 it was removed.'),
      '#states'        => [
        'visible' => [
          ':input[name="smart_ip_data_source"]' => ['value' => self::sourceId()],
        ],
      ],
    ];
    $form['smart_ip_data_source_selection']['ipinfodb_api_key'] = [
      '#type'          => 'textfield',
      '#title'         => t('IPInfoDB API key'),
      '#description'   => t(
        'The use of IPInfoDB.com service requires API key. Registration for the 
        new API key is free, sign up @here.', [
          '@here' => Link::fromTextAndUrl(t('here'), Url::fromUri('http://www.ipinfodb.com/register.php'))->toString(),
        ]
      ),
      '#default_value' => $config->get('api_key'),
      '#states'        => [
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
      if ($formState->isValueEmpty('ipinfodb_api_key')) {
        $formState->setErrorByName('ipinfodb_api_key', t('Please provide IPInfoDB API key.'));
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
      $config->set('version', $formState->getValue('ipinfodb_api_version'))
        ->set('api_key', $formState->getValue('ipinfodb_api_key'))
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
