<?php

namespace Drupal\merlinone;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;

/**
 * MerlinOne API service.
 */
class MerlinOneApi implements MerlinOneApiInterface {

  use StringTranslationTrait;

  /**
   * The minimum dimension of the image before resampling.
   */
  const MIN_RESAMPLE_DIMENSION = 256;

  /**
   * GuzzleHttp\Client definition.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * The logger channel for MerlinOne.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * The MIME type guesser.
   *
   * @var \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface
   */
  protected $mimeTypeGuesser;

  /**
   * Archive URL.
   *
   * @var string
   */
  protected $url;

  /**
   * Current API version.
   *
   * @var string
   */
  private $apiVersion;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \GuzzleHttp\Client $http_client
   *   Http client service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Logger factory service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   String translation manager.
   * @param \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface $mime_type_guesser
   *   The MIME Type guesser.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Client $http_client, LoggerChannelFactoryInterface $logger_factory, TranslationInterface $string_translation, MimeTypeGuesserInterface $mime_type_guesser) {
    $this->configFactory = $config_factory;
    $this->httpClient = $http_client;
    $this->logger = $logger_factory->get('merlinone');
    $this->stringTranslation = $string_translation;
    $this->mimeTypeGuesser = $mime_type_guesser;
  }

  /**
   * {@inheritdoc}
   */
  public function setArchiveUrl($url) {
    if (substr($url, -1, 1) !== '/') {
      $url = $url . '/';
    }

    $this->url = $url;
  }

  /**
   * {@inheritdoc}
   */
  public function getArchiveUrl() {
    if (!$this->url) {
      $config = $this->configFactory->get('merlinone.settings');
      $this->setArchiveUrl($config->get('archive_url'));
    }

    return $this->url;
  }

  /**
   * {@inheritdoc}
   */
  public function getMxUrl() {
    return $this->getArchiveUrl() . 'mx?altprofile=cms&cmstype=drupal';
  }

  /**
   * {@inheritdoc}
   */
  public function createFileFromItem($item, $directory) {
    $config = $this->configFactory->get('merlinone.settings');
    $max_dimension = $config->get('max_image_dimension');

    $parsed_item_url = UrlHelper::parse($item->url);

    // Need to check if the SODA url contains a cropinfo setting, if so, need to
    // remove it.
    if (!empty($parsed_item_url['query']['cropinfo'])) {
      unset($parsed_item_url['query']['cropinfo']);
    }

    // If a maximum dimension has been configured in the Merlin Object browser
    // settings, need to apply it.
    if ($this->sodaAllowsResampling() && $max_dimension > self::MIN_RESAMPLE_DIMENSION) {
      $parsed_item_url['query']['cropinfo'] = 'd' . $max_dimension;
    }

    // Create URL.
    $item_url = Url::fromUri($parsed_item_url['path'], $parsed_item_url)->toString();
    $filename = $item->cobject205;

    // Destination is the upload directory and the filename.
    $destination = file_stream_wrapper_uri_normalize($directory . '/' . $filename);

    try {
      // Get the file data.
      $response = $this->httpClient->get($item_url);
      $data = (string) $response->getBody();

      // Save it to the filesystem.
      $local = file_unmanaged_save_data($data, $destination, FILE_EXISTS_RENAME);
    }
    catch (RequestException $exception) {
      drupal_set_message($this->t('Failed to fetch file due to error "%error"', ['%error' => $exception->getMessage()]), 'error');
      return NULL;
    }
    if (!$local) {
      drupal_set_message($this->t('@remote could not be saved to @path.', ['@remote' => $item_url, '@path' => $destination]), 'error');
      return NULL;
    }

    // Find the MIME type.
    $mime = $response->getHeader('Content-Type');
    $mime = count($mime) ? $mime[0] : $this->mimeTypeGuesser->guess($local);

    // Create a managed file entity.
    return File::create([
      'filename' => $filename,
      'uri' => $local,
      'filemime' => $mime,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function sodaAllowsResampling() {
    return version_compare($this->getSodaVersion(), '1.0.27') >= 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getSodaVersion() {
    if (!$this->apiVersion) {
      $version = '';
      $data = '';

      try {
        $response = $this->httpClient->get($this->getArchiveUrl() . 'soda');
        $data = $response->getBody()->getContents();
      }
      catch (RequestException $exception) {
        $this->logger->error('Failed to fetch SODA version: %e', ['%e' => $exception->getMessage()]);
        drupal_set_message($this->t('Failed to fetch SODA version: "%error"', ['%error' => $exception->getMessage()]), 'error');
      }

      if (strpos($data, 'SODA Version ') !== FALSE) {
        $version = substr($data, strpos($data, 'SODA Version ') + 13);
        if (strpos($version, '<br') !== FALSE) {
          $version = substr($version, 0, strpos($version, '<br'));
        }
      }

      $this->apiVersion = $version;
    }

    return $this->apiVersion;
  }

}
