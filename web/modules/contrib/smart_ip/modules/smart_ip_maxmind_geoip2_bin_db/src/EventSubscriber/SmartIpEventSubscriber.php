<?php

/**
 * @file
 * Contains \Drupal\smart_ip_maxmind_geoip2_bin_db\EventSubscriber\SmartIpEventSubscriber.
 */

namespace Drupal\smart_ip_maxmind_geoip2_bin_db\EventSubscriber;

use Drupal\smart_ip_maxmind_geoip2_bin_db\DatabaseFileUtility;
use Drupal\smart_ip_maxmind_geoip2_bin_db\MaxmindGeoip2BinDb;
use Drupal\smart_ip\DatabaseFileUtilityBase;
use Drupal\smart_ip\GetLocationEvent;
use Drupal\smart_ip\AdminSettingsEvent;
use Drupal\smart_ip\DatabaseFileEvent;
use Drupal\smart_ip\SmartIpEventSubscriberBase;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\StreamWrapper\PrivateStream;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Core functionality of this Smart IP data source module.
 * Listens to Smart IP override events.
 *
 * @package Drupal\smart_ip_maxmind_geoip2_bin_db\EventSubscriber
 */
class SmartIpEventSubscriber extends SmartIpEventSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public static function sourceId() {
    return 'maxmind_geoip2_bin_db';
  }

  /**
   * {@inheritdoc}
   */
  public static function configName() {
    return 'smart_ip_maxmind_geoip2_bin_db.settings';
  }

  /**
   * {@inheritdoc}
   */
  public function processQuery(GetLocationEvent $event) {
    if ($event->getDataSource() == self::sourceId()) {
      $config     = \Drupal::config(self::configName());
      $autoUpdate = $config->get('db_auto_update');
      $customPath = $config->get('bin_file_custom_path');
      $version    = $config->get('version');
      $edition    = $config->get('edition');
      $location   = $event->getLocation();
      $ipAddress  = $location->get('ipAddress');
      $folder     = DatabaseFileUtility::getPath($autoUpdate, $customPath);
      $file       = DatabaseFileUtility::getFilename($version, $edition);
      $dbFile     = "$folder/$file";
      if (!file_exists($dbFile)) {
        \Drupal::logger('smart_ip')->error(t('The database file %file does not exist or has been moved.', [
            '%file' => $dbFile,
          ])
        );
        return;
      }
      if (class_exists('\MaxMind\Db\Reader')) {
        $reader = new \MaxMind\Db\Reader($dbFile);
        $raw    = $reader->get($ipAddress);
      }
      else {
        $reader  = new \GeoIp2\Database\Reader($dbFile);
        $edition = $config->get('edition');
        if ($edition == MaxmindGeoip2BinDb::COUNTRY_EDITION) {
          $record = $reader->country($ipAddress);
        }
        else {
          $record = $reader->city($ipAddress);
        }
        $raw = $record->jsonSerialize();
      }
      $lang = \Drupal::languageManager()->getCurrentLanguage()->getId();
      if (class_exists('\MaxMind\Db\Reader')) {
        $reader->close();
      }
      if (!isset($raw['country']['names'][$lang])) {
        // The current language is not yet supported by MaxMind, use English as
        // default language.
        $lang = 'en';
      }
      $country       = isset($raw['country']['names'][$lang]) ? $raw['country']['names'][$lang] : '';
      $countryCode   = isset($raw['country']['iso_code']) ? $raw['country']['iso_code'] : '';
      $region        = isset($raw['subdivisions'][0]['names'][$lang]) ? $raw['subdivisions'][0]['names'][$lang] : '';
      $regionCode    = isset($raw['subdivisions'][0]['iso_code']) ? $raw['subdivisions'][0]['iso_code'] : '';
      $city          = isset($raw['city']['names'][$lang]) ? $raw['city']['names'][$lang] : '';
      $zip           = isset($raw['postal']['code']) ? $raw['postal']['code'] : '';
      $latitude      = isset($raw['location']['latitude']) ? $raw['location']['latitude'] : '';
      $longitude     = isset($raw['location']['longitude']) ? $raw['location']['longitude'] : '';
      $timeZone      = isset($raw['location']['time_zone']) ? $raw['location']['time_zone'] : '';
      $isEuCountry   = isset($raw['country']['is_in_european_union']) ? $raw['country']['is_in_european_union'] : '';
      $isGdprCountry = isset($record['country']['isGdprCountry']) ? $record['country']['isGdprCountry'] : '';
      $location->set('originalData', $raw)
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
    $config     = \Drupal::config(self::configName());
    $autoUpdate = $config->get('db_auto_update');
    $form       = $event->getForm();
    /** @var \Drupal\Core\File\FileSystem $filesystem */
    $filesystem    = \Drupal::service('file_system');
    $privateFolder = $filesystem->realpath(DatabaseFileUtilityBase::DRUPAL_FOLDER);
    $errorSourceId = \Drupal::state()->get('smart_ip.request_db_error_source_id') ?: '';
    $autoDbUpdateLabel = t('Automatic MaxMind GeoIP2 binary database update');
    if (!$autoUpdate && $errorSourceId == self::sourceId()) {
      $form['smart_ip_bin_database_update']['#access'] = FALSE;
    }
    if (empty($privateFolder)) {
      $privateFolder = t('your "smart_ip" labelled folder inside your Drupal private folder (currently it is not yet set)');
    }
    else {
      $privateFolder = t('@path (default)', ['@path' => $privateFolder]);
    }
    $form['smart_ip_data_source_selection']['smart_ip_data_source']['#options'][self::sourceId()] = t(
      "Use MaxMind GeoIP2 binary database. It is the evolution of MaxMind's 
      original GeoIP binary database or now called GeoIP Legacy. This MaxMind's 
      binary database uses a custom binary format to maximize lookup speed and 
      accessible via two available APIs: @maxmind_db_reader_api which includes 
      an optional C extension that you may @install to dramatically increase the 
      performance of lookups in GeoIP2 binary database and the default 
      @geoip2_api. Lite version binary database can be downloaded @here. For 
      licensed version, you will need to enter your license below and the binary 
      database file can be downloaded @here2 (you will need to login to your 
      MaxMind account first). The binary database is roughly 130MB, and there's 
      an option below to enable the automatic download/extraction of it. If you 
      prefer to manually download the file %file_lite_city (if Lite version City 
      edition) or %file_licensed_city (if Licensed version City edition) or 
      %file_lite_country (if Lite version Country edition) or 
      %file_licensed_country (if Licensed version Country edition), it must be 
      uploaded to your server at @path or to your defined custom path (Note: It 
      is your responsibility to update them manually every month and to define such 
      a path, please set '@auto_update_label' to 'No', and fill in the field which 
      will then appear).", [
        '@maxmind_db_reader_api' => Link::fromTextAndUrl(t('MaxMind DB Reader PHP API'), Url::fromUri('https://github.com/maxmind/MaxMind-DB-Reader-php'))->toString(),
        '@install'    => Link::fromTextAndUrl(t('install'), Url::fromUri('https://www.webfoobar.com/node/71#install-maxmind-php-c-extension'))->toString(),
        '@geoip2_api' => Link::fromTextAndUrl(t('GeoIP2 PHP API'), Url::fromUri('http://maxmind.github.io/GeoIP2-php'))->toString(),
        '@here'       => Link::fromTextAndUrl(t('here'), Url::fromUri('https://dev.maxmind.com/geoip/geoip2/geolite2'))->toString(),
        '@here2'      => Link::fromTextAndUrl(t('here'), Url::fromUri('https://www.maxmind.com/en/download_files'))->toString(),
        '%file_lite_city'        => MaxmindGeoip2BinDb::FILENAME_LITE_CITY . MaxmindGeoip2BinDb::FILE_EXTENSION,
        '%file_licensed_city'    => MaxmindGeoip2BinDb::FILENAME_LINCENSED_CITY . MaxmindGeoip2BinDb::FILE_EXTENSION,
        '%file_lite_country'     => MaxmindGeoip2BinDb::FILENAME_LITE_COUNTRY . MaxmindGeoip2BinDb::FILE_EXTENSION,
        '%file_licensed_country' => MaxmindGeoip2BinDb::FILENAME_LINCENSED_COUNTRY . MaxmindGeoip2BinDb::FILE_EXTENSION,
        '@path'                  => $privateFolder,
        '@auto_update_label'     => $autoDbUpdateLabel,
      ]
    );
    $form['smart_ip_data_source_selection']['maxmind_geoip2_bin_db_version'] = [
      '#type'  => 'select',
      '#title' => t('MaxMind GeoIP2 binary database version'),
      '#description' => t('Select version of MaxMind GeoIP2 binary database.'),
      '#options' => [
        MaxmindGeoip2BinDb::LINCENSED_VERSION => t('Licensed'),
        MaxmindGeoip2BinDb::LITE_VERSION      => t('Lite'),
      ],
      '#default_value' => $config->get('version'),
      '#states' => [
        'visible' => [
          ':input[name="smart_ip_data_source"]' => ['value' => self::sourceId()],
        ],
      ],
    ];
    $form['smart_ip_data_source_selection']['maxmind_geoip2_bin_db_edition'] = [
      '#type'  => 'select',
      '#title' => t('MaxMind GeoIP2 binary database edition'),
      '#description' => t('Select edition of MaxMind GeoIP2 binary database.'),
      '#options' => [
        MaxmindGeoip2BinDb::CITY_EDITION    => t('City'),
        MaxmindGeoip2BinDb::COUNTRY_EDITION => t('Country'),
      ],
      '#default_value' => $config->get('edition'),
      '#states' => [
        'visible' => [
          ':input[name="smart_ip_data_source"]' => ['value' => self::sourceId()],
        ],
      ],
    ];
    $form['smart_ip_data_source_selection']['maxmind_geoip2_bin_db_license_key'] = [
      '#type'  => 'textfield',
      '#title' => t('MaxMind GeoIP2 license key'),
      '#description' => t(
        "Enter your MaxMind GeoIP2 account's license key (view your license key 
        @here). This is required for licensed version.", [
          '@here' => Link::fromTextAndUrl(t('here'), Url::fromUri('https://www.maxmind.com/en/my_license_key'))->toString(),
        ]
      ),
      '#default_value' => $config->get('license_key'),
      '#size' => 30,
      '#states' => [
        'visible' => [
          ':input[name="smart_ip_data_source"]' => ['value' => self::sourceId()],
          ':input[name="maxmind_geoip2_bin_db_version"]' =>['value' => MaxmindGeoip2BinDb::LINCENSED_VERSION],
        ],
      ],
    ];
    $form['smart_ip_data_source_selection']['maxmind_geoip2_bin_db_auto_update'] = [
      '#type'  => 'select',
      '#title' => $autoDbUpdateLabel,
      '#description' => t(
        'MaxMind GeoIP2 binary database will be automatically updated via 
        cron.php every Wednesday (for licensed version) and every first 
        Wednesday of the month (for lite or free version). MaxMind GeoIP2 
        updates their database every Tuesday for licensed version and every 
        first Tuesday of the month for lite or free version. @cron must be enabled 
        for this to work.', [
          '@cron'   => Link::fromTextAndUrl(t('Cron'), Url::fromRoute('system.cron_settings'))->toString(),
        ]
      ),
      '#options' => [
        TRUE  => t('Yes'),
        FALSE => t('No'),
      ],
      '#default_value' => intval($autoUpdate),
      '#states' => [
        'visible' => [
          ':input[name="smart_ip_data_source"]' => ['value' => self::sourceId()],
        ],
      ],
    ];
    $form['smart_ip_data_source_selection']['maxmind_geoip2_bin_db_custom_path'] = [
      '#type'  => 'textfield',
      '#title' => t('MaxMind GeoIP2 binary database custom path'),
      '#description' => t(
        'Define the path where the MaxMind GeoIP2 binary database file is 
        located in your server (Note: it is your responsibility to add security 
        on this path. See the online handbook for @security). Include preceding 
        slash but do not include trailing slash. This is useful for multi Drupal 
        sites with each of their Smart IP module looks to a common MaxMind 
        GeoIP2 binary database and it can be also useful for server with 
        installed GeoIPLookup CLI tool where its MaxMind binary database file 
        can be used here (usually its path is located at /usr/share/GeoIP). This 
        path will be ignored if "Automatic MaxMind binary database update" is 
        enabled which uses the Drupal private file system path. Leave it blank 
        if you prefer the default Drupal private file system path.', [
          '@security' => Link::fromTextAndUrl(t('more information about securing private files'), Url::fromUri('https://www.drupal.org/documentation/modules/file'))->toString(),
        ]
      ),
      '#default_value' => $config->get('bin_file_custom_path'),
      '#states' => [
        'visible' => [
          ':input[name="smart_ip_data_source"]' => ['value' => self::sourceId()],
          ':input[name="maxmind_geoip2_bin_db_auto_update"]' => ['value' => '0'],
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
    $formState = $event->getFormState();
    if ($formState->getValue('smart_ip_data_source') == self::sourceId()) {
      $autoUpdate = $formState->getValue('maxmind_geoip2_bin_db_auto_update');
      if ($formState->getValue('maxmind_geoip2_bin_db_version') == MaxmindGeoip2BinDb::LINCENSED_VERSION && $formState->isValueEmpty('maxmind_geoip2_bin_db_license_key')) {
        $formState->setErrorByName('maxmind_geoip2_bin_db_license_key', t('Please provide MaxMind GeoIP2 license key.'));
      }
      if ($autoUpdate || (!$autoUpdate && $formState->isValueEmpty('maxmind_geoip2_bin_db_custom_path'))) {
        if (empty(PrivateStream::basePath())) {
          $formState->setErrorByName('maxmind_geoip2_bin_db_auto_update', t(
              'Your private file system path is not yet configured. Please check 
              your @filesystem.', [
                '@filesystem' => Link::fromTextAndUrl(t('File system'), Url::fromRoute('system.file_system_settings'))->toString(),
              ]
            )
          );
        }
        else {
          /** @var \Drupal\Core\File\FileSystem $filesystem */
          $filesystem = \Drupal::service('file_system');
          $privateFolder = $filesystem->realpath(DatabaseFileUtilityBase::DRUPAL_FOLDER);
          $file = DatabaseFileUtility::getFilename($formState->getValue('maxmind_geoip2_bin_db_version'), $formState->getValue('maxmind_geoip2_bin_db_edition'));
          if (!file_exists("$privateFolder/$file")) {
            if ($autoUpdate) {
              $message = t(
                'Initially you need to manually download the @file and upload it 
              to your server at @path. The next succeeding updates should be 
              automatic.', [
                '@file' => $file,
                '@path' => $privateFolder,
              ]);
            }
            else {
              $message = t('Please upload the @file at @path.', [
                '@file' => $file,
                '@path' => $privateFolder,
              ]);
            }
            $formState->setErrorByName('maxmind_geoip2_bin_db_auto_update', $message);
          }
        }
      }
      elseif (!$formState->isValueEmpty('maxmind_geoip2_bin_db_custom_path')) {
        $folder = $formState->getValue('maxmind_geoip2_bin_db_custom_path');
        $file = DatabaseFileUtility::getFilename($formState->getValue('maxmind_geoip2_bin_db_version'), $formState->getValue('maxmind_geoip2_bin_db_edition'));
        if (!file_exists("$folder/$file")) {
          $formState->setErrorByName('maxmind_geoip2_bin_db_auto_update', t('Please upload the @file at @path.', [
              '@file' => $file,
              '@path' => $folder,
            ])
          );
        }
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
      $config->set('version', $formState->getValue('maxmind_geoip2_bin_db_version'))
        ->set('edition', $formState->getValue('maxmind_geoip2_bin_db_edition'))
        ->set('license_key', $formState->getValue('maxmind_geoip2_bin_db_license_key'))
        ->set('db_auto_update', $formState->getValue('maxmind_geoip2_bin_db_auto_update'))
        ->set('bin_file_custom_path', $formState->getValue('maxmind_geoip2_bin_db_custom_path'))
        ->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function manualUpdate(DatabaseFileEvent $event) {
    $dataSource = \Drupal::config('smart_ip.settings')->get('data_source');
    if ($dataSource == self::sourceId()) {
      DatabaseFileUtility::downloadDatabaseFile();
    }
  }

  /**
   * MaxMind GeoIP2 updates the binary database every Tuesday, and we download
   * every Wednesday for licensed version. Every first Tuesday of the month for
   * lite or free version, and we download every first Wednesday of the month.
   * That means that we only want to download if the current database was
   * downloaded prior to the most recently available version.
   */
  public function cronRun(DatabaseFileEvent $event) {
    $dataSource = \Drupal::config('smart_ip.settings')->get('data_source');
    if ($dataSource == self::sourceId()) {
      $config = \Drupal::config(self::configName());
      $autoUpdate = $config->get('db_auto_update');
      $version = $config->get('version');
      $lastUpdateTime = \Drupal::state()->get('smart_ip_maxmind_geoip2_bin_db.last_update_time') ?: 0;
      if ($version == MaxmindGeoip2BinDb::LINCENSED_VERSION) {
        $frequency = DatabaseFileUtility::DOWNLOAD_WEEKLY;
      }
      elseif ($version == MaxmindGeoip2BinDb::LITE_VERSION) {
        $frequency = DatabaseFileUtility::DOWNLOAD_MONTHLY;
      }
      if (DatabaseFileUtility::needsUpdate($lastUpdateTime, $autoUpdate, $frequency)) {
        DatabaseFileUtility::downloadDatabaseFile();
      }
    }
  }

}
