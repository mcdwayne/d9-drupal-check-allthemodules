<?php

namespace Drupal\streamy;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Site\Settings;
use Drupal\streamy\StreamWrapper\FlySystemHelper;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

/**
 * A factory for Streamy filesystem helper.
 */
class StreamyFactory {

  /**
   * Default settings.
   *
   * @var array
   */
  protected $defaults = [
    'config'      => [],
    'name'        => '',
    'description' => '',
    'private'     => FALSE,
  ];

  protected $streamyStreamManager;
  protected $streamyCDNManager;
  protected $configFactory;
  protected $logger;

  /**
   * A cache of filesystems.
   *
   * @var \League\Flysystem\FilesystemInterface[]
   */
  protected $filesystems = [];

  /**
   * The Flysystem plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $pluginManager;

  /**
   * @var \GuzzleHttp\Client
   */
  protected $http_client;

  /**
   * Settings for stream wrappers.
   *
   * @var array
   */
  protected $settings = [];

  /**
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * StreamyFactory constructor.
   *
   * @param \Drupal\Core\File\FileSystemInterface      $filesystem
   * @param \Drupal\streamy\StreamyStreamManager       $streamyStreamManager
   * @param \Drupal\streamy\StreamyCDNManager          $streamyCDNManager
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   * @param \Psr\Log\LoggerInterface                   $logger
   * @param \GuzzleHttp\Client                         $http_client
   * @param bool                                       $isPrivate
   * @param \Drupal\Core\Queue\QueueFactory            $queueFactory
   */
  public function __construct(FileSystemInterface $filesystem,
                              StreamyStreamManager $streamyStreamManager,
                              StreamyCDNManager $streamyCDNManager,
                              ConfigFactoryInterface $configFactory,
                              LoggerInterface $logger,
                              Client $http_client,
                              QueueFactory $queueFactory,
                              $isPrivate = FALSE) {
    $this->streamyStreamManager = $streamyStreamManager;
    $this->streamyCDNManager = $streamyCDNManager;
    $this->configFactory = $configFactory;
    $this->logger = $logger;
    $this->http_client = $http_client;
    $this->queueFactory = $queueFactory;

    $settings = $this->getSchemesSettings();
    // Apply defaults and validate registered services.
    foreach ($settings as $scheme => $configuration) {
      // The settings.php file could be changed before rebuilding the container.
      if (!$filesystem->validScheme($scheme) || !is_array($configuration)) {
        continue;
      }

      $this->settings[$scheme] = $configuration + $this->defaults;
    }
  }

  /**
   * @return array
   */
  public function getSchemesSettings() {
    $config = $this->configFactory->get('streamy.schemes')->getRawData();
    $baseConfig = isset($config['schemes']) ? $config['schemes'] : [];
    $phpConfig = Settings::get('streamy', []);

    $phpSettings = $this->preserveBaseSchemeSettings($phpConfig);
    $settings = array_merge($baseConfig, $phpSettings);
    return $settings;
  }

  public function getSchemeLevels() {
    return ['master', 'slave'];
  }

  /**
   * @param $phpSettings
   * @return mixed
   */
  private function preserveBaseSchemeSettings($phpSettings) {
    $untouchablesSchemes = ['streamy', 'streamypvt'];

    foreach ($phpSettings as $scheme => $schemeSetting) {
      if (in_array($scheme, $untouchablesSchemes)) {
        unset($phpSettings[$scheme]);
      }
    }
    return $phpSettings;
  }

  /**
   * Returns the filesystem for a given scheme.
   *
   * @param string $scheme
   *   The scheme.
   *
   * @return \League\Flysystem\FilesystemInterface|false
   *   The filesystem for the scheme.
   */
  public function getFilesystem(string $scheme) {
    if (!isset($this->filesystems[$scheme])) {
      if (in_array($scheme, $this->getSchemes())) {
        $this->filesystems[$scheme] = new FlySystemHelper($scheme,
                                                          $this->streamyStreamManager,
                                                          $this->streamyCDNManager,
                                                          $this->configFactory,
                                                          $this->logger,
                                                          $this->settings[$scheme],
                                                          $this->http_client,
                                                          $this->queueFactory);
      } else {
        return FALSE;
      }
    }
    return $this->filesystems[$scheme];
  }

  /**
   * Returns a list of valid schemes.
   *
   * @return string[]
   *   The list of valid schemes.
   */
  public function getSchemes() {
    return array_keys($this->settings);
  }

  /**
   * Finds the settings for a given scheme.
   *
   * @param string $scheme
   *   The scheme.
   *
   * @return array
   *   The settings array from settings.php.
   */
  public function getSettings($scheme) {
    return isset($this->settings[$scheme]) ? $this->settings[$scheme] :
      [];
  }

}
