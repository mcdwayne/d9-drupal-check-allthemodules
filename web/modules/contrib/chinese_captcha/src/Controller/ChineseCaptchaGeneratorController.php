<?php

/**
 * @file
 * Contains chinese CAPTCHA response class.
 */

namespace Drupal\chinese_captcha\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\chinese_captcha\Response\ChineseCaptchaResponse;
use Drupal\Core\Config\Config;

/**
 * Controller which generates the image from defined settings.
 */
class ChineseCaptchaGeneratorController implements ContainerInjectionInterface {

  /**
   * Image Captcha config storage.
   *
   * @var Config
   */
  protected $config;

  /**
   * Watchdog logger channel for captcha.
   *
   * @var LoggerChannelInterface
   */
  protected $logger;

  /**
   * Kill Switch for page caching.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  protected $killSwitch;

  /**
   * {@inheritdoc}
   */
  public function __construct(Config $config, LoggerChannelInterface $logger, KillSwitch $kill_switch) {
    $this->config = $config;
    $this->logger = $logger;
    $this->killSwitch = $kill_switch;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')->get('chinese_captcha.settings'),
      $container->get('logger.factory')->get('captcha'),
      $container->get('page_cache_kill_switch')
    );
  }

  /**
   * Main method that throw ChineseCaptchaResponse object to generate image.
   *
   * @return ChineseCaptchaResponse
   *   Make a ChineseCaptchaResponse with the correct configuration and return it.
   */
  public function image() {
    $this->killSwitch->trigger();
    return new ChineseCaptchaResponse($this->config, $this->logger);
  }

}
