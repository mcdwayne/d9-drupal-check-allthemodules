<?php

namespace Drupal\google_calendar;

use Drupal\Core\Config\ConfigFactory;
use Drupal\file\Entity\File;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

class GoogleCalendarSecretsManagedFile implements GoogleCalendarSecretsFileInterface {

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
  const CONFIG_SECRET_FILE_ID = 'secret_file_id';

  /**
   * GoogleCalendarSecretsManagedFile constructor.
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
   * Check that the secret file id is valid and fix it if not. Return the fixed ID or FALSE.
   *
   * @return bool|int
   *   FALSE if the ID was not valid, otherwise the ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @throws \Drupal\google_calendar\GoogleCalendarSecretsException
   *   If the file was defined badly.
   */
  private function ensureFileIdValid() {
    $secret_id = $this->config->get(self::CONFIG_SECRET_FILE_ID);
    if ($secret_id === NULL) {
      // Check to see if there is a _url set, which was used previously.
      // If so, convert it to a file id (if possible) and set in config.
      $secret_url = $this->config->get('secret_file_uri');
      if ($secret_url === NULL) {
        return FALSE;
      }

      $file = \Drupal::entityTypeManager()
        ->getStorage('file')
        ->loadByProperties(['uri' => $secret_url]);

      if ($file === NULL) {
        $this->logger->warning('secrets file using old url config, but no managed file found.');
        throw new GoogleCalendarSecretsException('Secrets file url is not a managed file.');
      }

      $file = reset($file);
      $secret_id = $file->id();
      $gcal_config_edit = $this->configFactory->getEditable(self::CONFIG_NAME);
      $gcal_config_edit->set(self::CONFIG_SECRET_FILE_ID, $secret_id)->save();
      $gcal_config_edit->clear('secret_file_uri')->save();

      $this->logger->warning('secrets file using url config: converted @url to managed file id=@id.',
        ['@url' => $secret_url, '@id' => $secret_id]);
    }
    return (int)$secret_id;
  }

  /**
   * Return the managed file ID of the secrets file.
   *
   * @return bool|int
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\google_calendar\GoogleCalendarSecretsException
   */
  public function getFileId() {
    // Logically, ensureFileIdValid() is equivalent to:
    //   $secret_id = $this->config->get(self::CONFIG_SECRET_FILE_ID);

    $secret_id = $this->ensureFileIdValid();
    return $secret_id;
  }

  /**
   * Return the filename of the JSON Secrets file.
   *
   * @return string
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\google_calendar\GoogleCalendarSecretsException
   */
  public function getFilePath() {
    $secret_id = $this->getFileId();

    if (!is_numeric($secret_id)) {
      throw new GoogleCalendarSecretsException('Secrets file id has invalid value.');
    }
    if ($file = File::load($secret_id)) {
      return \Drupal::service('file_system')->realpath($file->getFileUri());
    }
    throw new GoogleCalendarSecretsException('Secrets managed file could not be loaded.');
  }

  /**
   * Get the secret file JSON from the JSON file from Google.
   *
   * @return bool|mixed
   *   A JSON array if the file was loaded and parsed ok, otherwise FALSE if
   *   no file has been set or the JSON was missing/invalid.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\google_calendar\GoogleCalendarSecretsException
   *   If the secrets file config is not valid, or the file could not be opened.
   */
  public function get() {
    $secret_id = $this->getFileId();

    if (!is_numeric($secret_id)) {
      throw new GoogleCalendarSecretsException('Secrets file id has invalid value.');
    }

    if ($file = File::load($secret_id)) {
      $filepath = \Drupal::service('file_system')->realpath($file->getFileUri());

      if (is_readable($filepath)) {
        $json = file_get_contents($filepath);
        $jsondict = json_decode($json, TRUE, 6, JSON_INVALID_UTF8_IGNORE);
        if ($jsondict !== NULL) {
          return $jsondict;
        }
        return FALSE;
      }
      throw new GoogleCalendarSecretsException('Unable to read managed secrets file: ' . $filepath);
    }
  }
}