<?php

/**
 * @file
 * Contains \Drupal\smart_ip_ip2location_bin_db\EventSubscriber\SmartIpEventSubscriber.
 */

namespace Drupal\smart_ip_ip2location_bin_db\EventSubscriber;

use Drupal\smart_ip_ip2location_bin_db\DatabaseFileUtility;
use Drupal\smart_ip_ip2location_bin_db\Ip2locationBinDb;
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
 * @package Drupal\smart_ip_ip2location_bin_db\EventSubscriber
 */
class SmartIpEventSubscriber extends SmartIpEventSubscriberBase {
  /**
   * {@inheritdoc}
   */
  public static function sourceId() {
    return 'ip2location_bin_db';
  }

  /**
   * {@inheritdoc}
   */
  public static function configName() {
    return 'smart_ip_ip2location_bin_db.settings';
  }

  /**
   * {@inheritdoc}
   */
  public function processQuery(GetLocationEvent $event) {
    if ($event->getDataSource() == self::sourceId()) {
      $config     = \Drupal::config(self::configName());
      $version    = $config->get('version');
      $edition    = $config->get('edition');
      $autoUpdate = $config->get('db_auto_update');
      $customPath = $config->get('bin_file_custom_path');
      $folder     = DatabaseFileUtility::getPath($autoUpdate, $customPath);
      $location   = $event->getLocation();
      $ipAddress  = $location->get('ipAddress');
      $ipVersion  = $location->get('ipVersion');
      $file       = DatabaseFileUtility::getFilename($version, $edition, $ipVersion);
      $dbFile     = "$folder/$file";
      $error      = $this->checkBinFile($dbFile, $ipVersion);
      if ($error['code']) {
        \Drupal::logger('smart_ip')->error(t('The database file %file does not exist or has been moved.', [
            '%file' => $dbFile,
          ])
        );
        return;
      }
      if ($config->get('caching_method') == Ip2locationBinDb::NO_CACHE) {
        $cachingMethod = \IP2Location\Database::FILE_IO;
      }
      elseif ($config->get('caching_method') == Ip2locationBinDb::MEMORY_CACHE) {
        $cachingMethod = \IP2Location\Database::MEMORY_CACHE;
      }
      elseif ($config->get('caching_method') == Ip2locationBinDb::SHARED_MEMORY) {
        $cachingMethod = \IP2Location\Database::SHARED_MEMORY;
      }
      if (!empty($dbFile) && !empty($cachingMethod)) {
        $reader = new \IP2Location\Database($dbFile, $cachingMethod);
        $record = $reader->lookup($ipAddress, \IP2Location\Database::ALL);
        foreach ($record as &$item) {
          if (strpos($item, 'Please upgrade') !== FALSE || strpos($item, 'Invalid IP address') !== FALSE || $item == '-') {
            // Make the value "This parameter is unavailable in selected .BIN
            // data file. Please upgrade." or "Invalid IP address" or "-" as
            // NULL.
            $item = NULL;
          }
        }
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
    $config     = \Drupal::config(self::configName());
    $autoUpdate = $config->get('db_auto_update');
    $form       = $event->getForm();
    /** @var \Drupal\Core\File\FileSystem $filesystem */
    $filesystem        = \Drupal::service('file_system');
    $privateFolder     = $filesystem->realpath(DatabaseFileUtilityBase::DRUPAL_FOLDER);
    $errorSourceId     = \Drupal::state()->get('smart_ip.request_db_error_source_id') ?: '';
    $autoDbUpdateLabel = t('Automatic IP2Location binary database update');
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
      "Use IP2Location binary database. It uses two binary database files; 
      one is for IPV4 address support and the other is for IPV6 address support. 
      Paid and free versions are available. You need to register first for an 
      account @here for lite version and login @here2 in able to download the 
      two binary database files. For licensed version, you need to buy their 
      product and they will provide you the login details and use it to login 
      @here3. You can download the files @here4 for lite version and @here5 for 
      licensed version. Recommended product ID are DB1 (if you need country 
      level only and more faster query) or DB11 (if you need more details but 
      this is less faster than DB1). The binary database is roughly 200MB, and 
      there's an option below to enable the automatic download/extraction of it 
      (not yet supported in free vesion). If you prefer to manually download the 
      two binary database files, they must be uploaded to your server at @path 
      or to your defined custom path (Note: It is your responsibility to update 
      them manually every month and to define such a path, please set 
      '@auto_update_label' to 'No', and fill in the field which will then appear)", [
        '@here'  => Link::fromTextAndUrl(t('here'), Url::fromUri('http://lite.ip2location.com/sign-up'))->toString(),
        '@here2' => Link::fromTextAndUrl(t('here'), Url::fromUri('http://lite.ip2location.com/login'))->toString(),
        '@here3' => Link::fromTextAndUrl(t('here'), Url::fromUri('https://www.ip2location.com/login'))->toString(),
        '@here4' => Link::fromTextAndUrl(t('here'), Url::fromUri('http://lite.ip2location.com/database'))->toString(),
        '@here5' => Link::fromTextAndUrl(t('here'), Url::fromUri('https://www.ip2location.com/download'))->toString(),
        '@path' => $privateFolder,
        '@auto_update_label' => $autoDbUpdateLabel,
      ]
    );
    $form['smart_ip_data_source_selection']['ip2location_bin_db_version'] = [
      '#type'  => 'select',
      '#title' => t('IP2Location binary database version'),
      '#description' => t('Select version of IP2Location binary database.'),
      '#options' => [
        Ip2locationBinDb::LINCENSED_VERSION => t('Licensed'),
        Ip2locationBinDb::LITE_VERSION      => t('Lite'),
      ],
      '#default_value' => $config->get('version'),
      '#states' => [
        'visible' => [
          ':input[name="smart_ip_data_source"]' => ['value' => self::sourceId()],
        ],
      ],
    ];
    $form['smart_ip_data_source_selection']['ip2location_bin_db_token'] = [
      '#type'  => 'textfield',
      '#title' => t('IP2Location download token'),
      '#description' => t(
        "Enter your IP2Location account's download token (view your download token 
        @here). This is required for licensed version.", [
          '@here' => Link::fromTextAndUrl(t('here'), Url::fromUri('https://www.ip2location.com/file-download'))->toString(),
        ]
      ),
      '#default_value' => $config->get('token'),
      '#size' => 70,
      '#states' => [
        'visible' => [
          ':input[name="smart_ip_data_source"]' => ['value' => self::sourceId()],
          ':input[name="ip2location_bin_db_version"]' =>['value' => Ip2locationBinDb::LINCENSED_VERSION],
          ':input[name="ip2location_bin_db_auto_update"]' =>['value' => '1'],
        ],
      ],
    ];
    $products = Ip2locationBinDb::products(Ip2locationBinDb::LINCENSED_VERSION);
    $prodOptions = [];
    foreach ($products as $code => $name) {
      $prodOptions[$code] = "$code: $name";
    }
    $form['smart_ip_data_source_selection']['ip2location_bin_db_edition_licensed'] = [
      '#type'        => 'select',
      '#title'       => t('IP2Location product edition (for licensed version)'),
      '#description' => t(
        'Select the purchased IP2Location product edition (@more).', [
          '@more' => Link::fromTextAndUrl(t('more info'), Url::fromUri('https://www.ip2location.com/databases'))->toString(),
        ]
      ),
      '#options' => $prodOptions,
      '#default_value' => $config->get('edition'),
      '#states'        => [
        'visible' => [
          ':input[name="smart_ip_data_source"]' => ['value' => self::sourceId()],
          ':input[name="ip2location_bin_db_version"]' =>['value' => Ip2locationBinDb::LINCENSED_VERSION],
        ],
      ],
    ];
    $products = Ip2locationBinDb::products(Ip2locationBinDb::LITE_VERSION);
    $prodOptions = [];
    foreach ($products as $code => $name) {
      $prodOptions[$code] = "$code: $name";
    }
    $form['smart_ip_data_source_selection']['ip2location_bin_db_edition_lite'] = [
      '#type'        => 'select',
      '#title'       => t('IP2Location product edition (for lite version)'),
      '#description' => t(
        'Select the IP2Location product edition (@more).', [
          '@more' => Link::fromTextAndUrl(t('more info'), Url::fromUri('https://lite.ip2location.com/'))->toString(),
        ]
      ),
      '#options' => $prodOptions,
      '#default_value' => $config->get('edition'),
      '#states'        => [
        'visible' => [
          ':input[name="smart_ip_data_source"]' => ['value' => self::sourceId()],
          ':input[name="ip2location_bin_db_version"]' =>['value' => Ip2locationBinDb::LITE_VERSION],
        ],
      ],
    ];
    $form['smart_ip_data_source_selection']['ip2location_bin_db_caching_method'] = [
      '#type'        => 'select',
      '#title'       => t('IP2Location caching method'),
      '#description' => t(
        '"No cache" - standard lookup with no cache and directly reads from the 
        database file. "Memory cache" - cache the database into memory to 
        accelerate lookup speed and read the whole database into a variable for 
        caching. "Shared memory" - cache whole database into system memory and 
        share among other scripts and websites. Please make sure your system 
        have sufficient RAM if enabling "Memory cache" or "Shared memory".'),
      '#options' => [
        Ip2locationBinDb::NO_CACHE      => t('No cache'),
        Ip2locationBinDb::MEMORY_CACHE  => t('Memory cache'),
        Ip2locationBinDb::SHARED_MEMORY => t('Shared memory'),
      ],
      '#default_value' => $config->get('caching_method'),
      '#states'        => [
        'visible' => [
          ':input[name="smart_ip_data_source"]' => ['value' => self::sourceId()],
        ],
      ],
    ];
    $form['smart_ip_data_source_selection']['ip2location_bin_db_auto_update'] = [
      '#type'  => 'select',
      '#title' => $autoDbUpdateLabel,
      '#description' => t(
        'IP2Location binary database will be automatically updated via 
        cron.php every first Wednesday of the month. @cron must be enabled 
        for this to work. Note: not yet available for Lite version.', [
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
    $form['smart_ip_data_source_selection']['ip2location_bin_db_custom_path'] = [
      '#type'  => 'textfield',
      '#title' => t('IP2Location binary database custom path'),
      '#description' => t(
        'Define the path where the IP2Location binary database files are 
        located in your server (Note: it is your responsibility to add security 
        on this path. See the online handbook for @security). Include preceding 
        slash but do not include trailing slash. This is useful for multi Drupal 
        sites with each of their Smart IP module looks to a common IP2Location 
        binary database. This path will be ignored if "Automatic MaxMind binary 
        database update" is enabled which uses the Drupal private file system 
        path. Leave it blank if you prefer the default Drupal private file 
        system path.', [
          '@security' => Link::fromTextAndUrl(t('more information about securing private files'), Url::fromUri('https://www.drupal.org/documentation/modules/file'))->toString(),
        ]
      ),
      '#default_value' => $config->get('bin_file_custom_path'),
      '#states' => [
        'visible' => [
          ':input[name="smart_ip_data_source"]' => ['value' => self::sourceId()],
          ':input[name="ip2location_bin_db_auto_update"]' =>['value' => '0'],
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
    $version    = $formState->getValue('ip2location_bin_db_version');
    $autoUpdate = $formState->getValue('ip2location_bin_db_auto_update');
    if ($version == Ip2locationBinDb::LINCENSED_VERSION) {
      $edition = $formState->getValue('ip2location_bin_db_edition_licensed');
    }
    else {
      $edition = $formState->getValue('ip2location_bin_db_edition_lite');
    }
    if ($formState->getValue('smart_ip_data_source') == self::sourceId()) {
      if ($formState->isValueEmpty('ip2location_bin_db_token') && $version == Ip2locationBinDb::LINCENSED_VERSION && $autoUpdate) {
        $formState->setErrorByName('ip2location_bin_db_token', t('Please provide IP2Location download token.'));
      }
      if ($autoUpdate && $version == Ip2locationBinDb::LITE_VERSION) {
        $formState->setErrorByName('ip2location_bin_db_auto_update', t('This feature is not yet available for Lite version.'));
      }
      if ($autoUpdate) {
        if (empty(PrivateStream::basePath())) {
          $formState->setErrorByName('ip2location_bin_db_auto_update', t(
            'Your private file system path is not yet configured. Please check 
            your @filesystem.', [
              '@filesystem' => Link::fromTextAndUrl(t('File system'), Url::fromRoute('system.file_system_settings'))
                ->toString(),
            ])
          );
        }
        else {
          /** @var \Drupal\Core\StreamWrapper\PrivateStream $privateStream */
          $privateStream = \Drupal::service('stream_wrapper.private');
          $file          = DatabaseFileUtility::getFilename($version, $edition, Ip2locationBinDb::IPV4_VERSION);
          $privateStream->setUri(DatabaseFileUtilityBase::DRUPAL_FOLDER);
          $folder = $privateStream->realpath();
          $dbFile = "$folder/$file";
          $error  = $this->checkBinFile($dbFile, Ip2locationBinDb::IPV4_VERSION);
          if ($error['code'] == Ip2locationBinDb::DB_NOT_EXIST_ERROR) {
            $formState->set('smart_ip_ip2location_bin_db_show_manual_update', TRUE);
            $formState->setRebuild(TRUE);
            $formState->setErrorByName('ip2location_bin_db_auto_update', t(
                'Initially you need to manually download the @file and upload it 
              to your server at @folder. The next succeeding updates should be 
              automatic.', [
                '@file'   => $file,
                '@folder' => $folder,
              ])
            );
          }
          else {
            $file = DatabaseFileUtility::getFilename($version, $edition, Ip2locationBinDb::IPV6_VERSION);
            $dbFile = "$folder/$file";
            $error  = $this->checkBinFile($dbFile, Ip2locationBinDb::IPV6_VERSION);
            if ($error['code'] == Ip2locationBinDb::DB_NOT_EXIST_ERROR) {
              $formState->set('smart_ip_ip2location_bin_db_show_manual_update', TRUE);
              $formState->setRebuild(TRUE);
              $formState->setErrorByName('ip2location_bin_db_auto_update', t(
                  'Initially you need to manually download the @file and upload it 
              to your server at @folder. The next succeeding updates should be 
              automatic.', [
                  '@file'   => $file,
                  '@folder' => $folder,
                ])
              );
            }
          }
        }
      }
      else {
        /** @var \Drupal\Core\StreamWrapper\PrivateStream $privateStream */
        $privateStream = \Drupal::service('stream_wrapper.private');
        $folder        = $formState->getValue('ip2location_bin_db_custom_path');
        $privateFolder = DatabaseFileUtilityBase::DRUPAL_FOLDER;
        if (!file_exists($privateFolder)) {
          $privateStream->mkdir($privateFolder, NULL, STREAM_MKDIR_RECURSIVE);
          file_prepare_directory($privateFolder);
        }
        if (empty($folder)) {
          $privateStream->setUri($privateFolder);
          $folder = $privateStream->realpath();
        }
        $file      = DatabaseFileUtility::getFilename($version, $edition, Ip2locationBinDb::IPV4_VERSION);
        $dbFile    = "$folder/$file";
        $error     = $this->checkBinFile($dbFile, Ip2locationBinDb::IPV4_VERSION);
        $errorCode = Ip2locationBinDb::DB_NO_ERROR;
        $errorMsg  = '';
        if ($error['code'] == Ip2locationBinDb::DB_NOT_EXIST_ERROR) {
          $errorCode = $error['code'];
          $errorMsg  = t(
            'The @file does not exist in @folder. Please provide a valid 
            path or select a proper IP2Location product edition or upload the 
            @file in @folder.', [
              '@file'   => $file,
              '@folder' => $folder,
            ]
          );
        }
        elseif ($error['code'] == Ip2locationBinDb::DB_READ_ERROR) {
          $errorCode = $error['code'];
          $errorMsg  = t('The IP2Location binary database IPV4 file @path is not valid or corrupted.', [
            '@path' => $dbFile,
          ]);
        }
        elseif ($error['code'] == Ip2locationBinDb::DB_LOAD_ERROR) {
          $errorCode = $error['code'];
          $errorMsg  = t('Loading IP2Location binary database IPV4 file @path failed: @error.', [
            '@path' => $dbFile,
            '@error' => $error['msg'],
          ]);
        }
        $file   = DatabaseFileUtility::getFilename($version, $edition, Ip2locationBinDb::IPV6_VERSION);
        $dbFile = "$folder/$file";
        $error  = $this->checkBinFile($dbFile, Ip2locationBinDb::IPV6_VERSION);
        if ($error['code'] == Ip2locationBinDb::DB_NOT_EXIST_ERROR) {
          $errorCode = $error['code'];
          $errorMsg  .= t(
            'The @file does not exist in @folder. Please provide a valid 
            path or select a proper IP2Location product edition or upload the 
            @file in @folder.', [
              '@file'   => $file,
              '@folder' => $folder,
            ]
          );
        }
        elseif ($error['code'] == Ip2locationBinDb::DB_READ_ERROR) {
          $errorCode = $error['code'];
          $errorMsg  .= t('The IP2Location binary database IPV6 file @path is not valid or corrupted.', [
            '@path' => $dbFile,
          ]);
        }
        elseif ($error['code'] == Ip2locationBinDb::DB_LOAD_ERROR) {
          $errorCode = $error['code'];
          $errorMsg  .= t('Loading IP2Location binary database IPV6 file @path failed: @error.', [
            '@path' => $dbFile,
            '@error' => $error['msg'],
          ]);
        }
        if ($errorCode) {
          $formState->setErrorByName('ip2location_bin_db_custom_path', $errorMsg);
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
      if ($formState->getValue('ip2location_bin_db_version') == Ip2locationBinDb::LINCENSED_VERSION) {
        $edition = $formState->getValue('ip2location_bin_db_edition_licensed');
      }
      else {
        $edition = $formState->getValue('ip2location_bin_db_edition_lite');
      }
      $config->set('version', $formState->getValue('ip2location_bin_db_version'))
        ->set('edition', $edition)
        ->set('token', $formState->getValue('ip2location_bin_db_token'))
        ->set('db_auto_update', $formState->getValue('ip2location_bin_db_auto_update'))
        ->set('caching_method', $formState->getValue('ip2location_bin_db_caching_method'))
        ->set('bin_file_custom_path', $formState->getValue('ip2location_bin_db_custom_path'))
        ->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function manualUpdate(DatabaseFileEvent $event) {
    $dataSource = \Drupal::config('smart_ip.settings')->get('data_source');
    if ($dataSource == self::sourceId()) {
      $ipVersion  = \Drupal::state()->get('smart_ip_ip2location_bin_db.current_ip_version_queue');
      if (empty($ipVersion)) {
        // Update IPv4 IP2Location binary database first
        $ipVersion = Ip2locationBinDb::IPV4_VERSION;
        \Drupal::state()->set('smart_ip_ip2location_bin_db.current_ip_version_queue', $ipVersion);
      }
      DatabaseFileUtility::downloadDatabaseFile($ipVersion);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function cronRun(DatabaseFileEvent $event) {
    $dataSource = \Drupal::config('smart_ip.settings')->get('data_source');
    if ($dataSource == self::sourceId()) {
      $config         = \Drupal::config(SmartIpEventSubscriber::configName());
      $autoUpdate     = $config->get('db_auto_update');
      $ipVersion      = \Drupal::state()->get('smart_ip_ip2location_bin_db.current_ip_version_queue');
      $lastUpdateTime = \Drupal::state()->get('smart_ip_ip2location_bin_db.last_update_time') ?: 0;
      if ($ipVersion || DatabaseFileUtility::needsUpdate($lastUpdateTime, $autoUpdate, DatabaseFileUtility::DOWNLOAD_MONTHLY)) {
        if (empty($ipVersion)) {
          // Update IPv4 IP2Location binary database first
          $ipVersion = Ip2locationBinDb::IPV4_VERSION;
          \Drupal::state()->set('smart_ip_ip2location_bin_db.current_ip_version_queue', $ipVersion);
        }
        DatabaseFileUtility::downloadDatabaseFile($ipVersion);
      }
    }
  }

  /**
   * Check IP2Location binary database file if valid.
   *
   * @param string $file
   *   IP2Location binary database file absolute path.
   * @param int $ipVersion
   *   The IP address version: 4 or 6.
   * @return array
   */
  private function checkBinFile($file, $ipVersion = Ip2locationBinDb::IPV4_VERSION) {
    $error['msg']  = '';
    $error['code'] =Ip2locationBinDb::DB_NO_ERROR;
    if (!file_exists($file)) {
      $error['code'] = Ip2locationBinDb::DB_NOT_EXIST_ERROR;
    }
    else {
      try {
        // Check IP2Location binary database file if valid
        if ($ipVersion == Ip2locationBinDb::IPV4_VERSION) {
          $ip = '8.8.8.8';
        }
        else {
          $ip = '2001:4860:4860::8888';
        }
        $reader = new \IP2Location\Database($file, \IP2Location\Database::FILE_IO);
        $record = $reader->lookup($ip, \IP2Location\Database::COUNTRY);
        if (strtotime($reader->getDate()) <= 0 || empty($record['countryCode'])) {
          $error['code'] = Ip2locationBinDb::DB_READ_ERROR;
        }
      } catch (\Exception $e) {
        $error['msg']  = $e->getMessage();
        $error['code'] = Ip2locationBinDb::DB_LOAD_ERROR;
      }
    }
    return $error;
  }

}
