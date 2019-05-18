<?php

namespace Drupal\nocaptcha_recaptcha;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;

/**
 * Class NocaptchaGenerator.
 *
 * @package Drupal\nocaptcha_recaptcha
 */
class NocaptchaGenerator implements NocaptchaGeneratorInterface {

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;
  /**
   * Drupal\Core\Logger\LoggerChannelFactory definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;
  /**
   * Drupal\Core\PageCache\ResponsePolicy\KillSwitch definition.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  protected $pageCacheKillSwitch;
  /**
   * Constructor.
   */
  public function __construct(ConfigFactory $config_factory, LoggerChannelFactory $logger_factory, KillSwitch $page_cache_kill_switch) {
    $this->configFactory = $config_factory;
    $this->loggerFactory = $logger_factory;
    $this->pageCacheKillSwitch = $page_cache_kill_switch;
  }

  /**
   * @return array
   */
  public function generate() {
    $config = $this->configFactory->get('nocaptcha_recaptcha.settings');
    return [
      '#type' => 'markup',
      '#markup' => '<div class="g-recaptcha" data-sitekey="' . $config->get('nocaptcha_site_key') . '"></div>'
    ];
  }
}
