<?php

namespace Drupal\onesignal\Config;

use Drupal\Core\Config\ConfigFactory;

/**
 * Manages Onesignal module configuration.
 *
 * @package Drupal\onesignal\Config
 */
class ConfigManager implements ConfigManagerInterface {
  
  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $config;
  
  /**
   * ConfigManager constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   The config factory service.
   */
  public function __construct(ConfigFactory $configFactory) {
    $this->config = $configFactory->getEditable('onesignal.config');
  }
  
  /**
   * {@inheritdoc}
   */
  public function getAppId() {
    return $this->config->get('onesignal_app_id');
  }
  
  /**
   * {@inheritdoc}
   */
  public function getOriginalAppId() {
    return $this->config->getOriginal('onesignal_app_id', FALSE);
  }
  
  /**
   * {@inheritdoc}
   */
  public function getSafariWebId() {
    return $this->config->get('onesignal_safari_web_id');
  }
  
  /**
   * {@inheritdoc}
   */
  public function getOriginalSafariWebId() {
    return $this->config->getOriginal('onesignal_safari_web_id', FALSE);
  }
  
  /**
   * {@inheritdoc}
   */
  public function getRestApiKey() {
    return $this->config->get('onesignal_rest_api_key');
  }
  
  /**
   * {@inheritdoc}
   */
  public function getAutoRegister() {
    return $this->config->get('onesignal_auto_register');
  }
  
  /**
   * {@inheritdoc}
   */
  public function getNotifyButton() {
    return $this->config->get('onesignal_notify_button');
  }
  
  /**
   * {@inheritdoc}
   */
  public function getLocalhostSecure() {
    return $this->config->get('onesignal_localhost_secure');
  }
  
  /**
   * {@inheritdoc}
   */
  public function getActionMessage() {
    return $this->config->get('onesignal_action_message');
  }
  
  /**
   * {@inheritdoc}
   */
  public function getAcceptButtonText() {
    return $this->config->get('onesignal_accept_button');
  }
  
  /**
   * {@inheritdoc}
   */
  public function getCancelButtonText() {
    return $this->config->get('onesignal_cancel_button');
  }
  
  /**
   * {@inheritdoc}
   */
  public function getWelcomeTitle() {
    return $this->config->get('onesignal_welcome_title');
  }
  
  /**
   * {@inheritdoc}
   */
  public function getWelcomeMessage() {
    return $this->config->get('onesignal_welcome_message');
  }
  
}
