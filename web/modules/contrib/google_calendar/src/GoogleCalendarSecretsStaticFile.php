<?php

namespace Drupal\google_calendar;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

class GoogleCalendarSecretsStaticFile implements GoogleCalendarSecretsFileInterface {

  /**
   * @var ConfigFactory
   */
  protected $configFactory;
  protected $config;

  /**
   * Logger
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  const CONFIG_NAME = 'google_calendar.default';
  const CONFIG_SECRET_FILE_NAME = 'secret_file_name';

  /**
   * GoogleCalendarSecretsStaticFile constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   */
  public function __construct(ConfigFactory $config, LoggerChannelFactoryInterface $loggerChannelFactory) {
    $this->configFactory = $config;
    $this->config = $config->get(self::CONFIG_NAME);
    $this->logger = $loggerChannelFactory->get('google_calendar');
  }

  /**
   * Return the filename of the JSON Secrets file.
   *
   * @return string
   */
  public function getFilePath() {
    $secret_file = $this->config->get(self::CONFIG_SECRET_FILE_NAME);
    return $secret_file;
  }

  /**
   * Get the secret file JSON from the JSON file from Google.
   *
   * @return bool|mixed
   *   A JSON array if the file was loaded and parsed ok, otherwise FALSE if
   *   no file has been set or the JSON was missing/invalid.
   *
   * @throws \Drupal\google_calendar\GoogleCalendarSecretsException
   *   If the secrets file config is not valid, or the file could not be opened.
   */
  public function get() {
    $secrets_file = $this->getFilePath();

    if (!$secrets_file) {
      throw new GoogleCalendarSecretsException('Secrets file name has invalid value.');
    }

    if (is_readable($secrets_file)) {
      $json = file_get_contents($secrets_file);
      $jsondict = json_decode($json, TRUE, 6, JSON_INVALID_UTF8_IGNORE);
      if ($jsondict !== NULL) {
        return $jsondict;
      }
      return FALSE;
    }
    throw new GoogleCalendarSecretsException('Unable to read static secrets file: ' . $secrets_file);
  }
}
