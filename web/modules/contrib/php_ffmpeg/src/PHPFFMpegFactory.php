<?php

namespace Drupal\php_ffmpeg;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Doctrine\Common\Cache\Cache;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;

/**
 * Factory class that provides a wrapper for the FFMpeg PHP extension.
 */
class PHPFFMpegFactory {

  /**
   * The cache backend that should be passed to the FFMpeg extension.
   *
   * @var \Doctrine\Common\Cache\Cache
   */
  protected $cache;

  /**
   * Logger channel that logs execution within FFMpeg extension to watchdog.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   *   The registered logger for this channel.
   */
  protected $logger;

  /**
   * The config object providing the module's config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Constructs a the factory object with injected dependencies.
   *
   * @param \Doctrine\Common\Cache\Cache $cache
   *   The cache backend.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   Prefix used for appending to cached item identifiers.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A configuration factory instance.
   */
  public function __construct(Cache $cache, LoggerChannelInterface $logger, ConfigFactoryInterface $config_factory) {
    $this->cache = $cache;
    $this->logger = $logger;
    $this->config = $config_factory->getEditable('php_ffmpeg.settings');
  }

  /**
   * Factory function for the FFMpeg object.
   *
   * @return \FFMpeg\FFMpeg
   */
  public function getFFMpeg() {
    return FFMpeg::create(
      $this->getFFMpegConfig(),
      $this->logger,
      $this->getFFMpegProbe()
    );
  }

  /**
   * Factory function for the FFProbe object.
   *
   * @return \FFMpeg\FFProbe
   */
  public function getFFMpegProbe() {
    return FFProbe::create(
      $this->getFFMpegConfig(),
      $this->logger,
      $this->cache
    );
  }

  /**
   * Provides configuration settings passed to FFMpeg classes' create methods.
   *
   * @return array
   *   Options based on settings as required by to \FFMpeg\FFMpeg::create().
   */
  protected function getFFMpegConfig() {
    return array_filter(array(
      'ffmpeg.binaries'  => $this->config->get('ffmpeg_binary'),
      'ffprobe.binaries' => $this->config->get('ffprobe_binary'),
      'timeout'          => $this->config->get('execution_timeout'),
      'ffmpeg.threads'   => $this->config->get('threads_amount'),
    ));
  }

}
