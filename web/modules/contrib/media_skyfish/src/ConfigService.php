<?php

namespace Drupal\media_skyfish;

use Drupal\Core\Config\ConfigFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Class ConfigService.
 */
class ConfigService {

  /**
   * Skyfish admin form configs.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Currently logged in user.
   *
   * @var \Drupal\Core\Entity\EntityInterface|null
   */
  protected $user;

  /**
   * Username to connect to Skyfish.
   *
   * @var string
   */
  protected $username;

  /**
   * Skyfish api key.
   *
   * @var string
   */
  protected $key;

  /**
   * Skyfish apie secret key.
   *
   * @var string
   */
  protected $secret;

  /**
   * Skyfish user password.
   *
   * @var string
   */
  protected $password;

  /**
   * Drupal logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Skyfish Widget pager items count.
   *
   * @var int
   */
  protected $items_per_page;

  /**
   * ConfigService constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A configuration array containing information about the plugin instance.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LoggerInterface $logger) {
    $this->config = $config_factory->get('media_skyfish.adminconfig');
    $this->user = \Drupal::entityTypeManager()->getStorage('user')->load(\Drupal::currentUser()->id());
    $this->logger = $logger;
    $this->initialize();
  }

  /**
   * Initialize function checks if user's or global data should be used.
   */
  private function initialize() {
    $this->key = empty($this->user->field_skyfish_api_user->value) ?
      $this->config->get('media_skyfish_api_key') : $this->user->field_skyfish_api_user->value;
    $this->secret = empty($this->user->field_skyfish_secret_api_key->value) ?
      $this->config->get('media_skyfish_api_secret') : $this->user->field_skyfish_secret_api_key->value;
    $this->username = empty($this->user->field_skyfish_username->value) ?
      $this->config->get('media_skyfish_global_user') : $this->user->field_skyfish_username->value;
    $this->password = empty($this->user->field_skyfish_password->value) ?
      $this->config->get('media_skyfish_global_password') : $this->user->field_skyfish_password->value;
    $this->items_per_page = $this->config->get('media_skyfish_items_per_page');
  }

  /**
   * Get Skyfish api key.
   *
   * @return string
   *   Skyfish api key.
   */
  public function getKey(): string {
    return $this->key;
  }

  /**
   * Set Skyfish api key.
   *
   * @param string
   *   Skyfish API key.
   *
   * @return \Drupal\media_skyfish\ConfigService $this
   */
  public function setKey(string $key): ConfigService {
    $this->key = $key;

    return $this;
  }

  /**
   * Check if key is not empty.
   *
   * @return bool
   *   If key is set, return TRUE, else FALSE.
   */
  public function hasKey() {
    return !empty($this->key);
  }

  /**
   * Get Skyfish secret api key.
   *
   * @return string
   *   Skyfish api secret.
   */
  public function getSecret(): string {
    return $this->secret;
  }

  /**
   * Set Skyfish secret api key.
   *
   * @param string $secret
   *   Skyfish API secret key.
   *
   * @return $this
   */
  public function setSecret(string $secret): ConfigService {
    $this->secret = $secret;

    return $this;
  }

  /**
   * Get Skyfish username.
   *
   * @return string
   *   Skyfish API username.
   */
  public function getUsername(): string {
    return $this->username;
  }

  /**
   * Set username to login to Skyfish.
   *
   * @param string $username
   *   Skyfish username.
   *
   * @return $this
   *   ConfigService.
   */
  public function setUsername(string $username): ConfigService {
    $this->username = $username;

    return $this;
  }

  /**
   * Get password to login to Skyfish.
   *
   * @return string
   *   Password for the Skyfish.
   */
  public function getPassword(): string {
    return $this->password;
  }

  /**
   * Set password to login to Skyfish.
   *
   * @param string $password
   *   Skyfish password.
   *
   * @return $this
   *   ConfigService.
   */
  public function setPassword(string $password): ConfigService {
    $this->password = $password;

    return $this;
  }

  /**
   * Get cache time.
   *
   * @return int|null
   *   Cache time.
   */
  public function getCacheTime() {
    $minutes = $this->config->get('media_skyfish_cache');
    $cache = new \DateTime('+' . $minutes . ' minutes');

    return $cache->getTimestamp();
  }

  /**
   * Get hmac for authentication.
   *
   * @return string
   *   Hmac string.
   */
  public function getHmac() {
    return hash_hmac('sha1', $this->key . ':' . time(), $this->secret);
  }

  /**
   * Get image count per page.
   *
   * @return int $items_per_page
   *   Number of items that should be shown on one page of Skyfish widget when
   * pager is enabled.
   */
  public function getItemsPerPage(): int {
    return $this->items_per_page;
  }

  /**
   * Set image count per page.
   *
   * @param int $items_per_page
   *   Number of items that should be shown on one page of Skyfish widget when
   * pager is enabled.
   *
   * @return $this
   *   ConfigService.
   */
  public function setItemsPerPage(int $items_per_page): ConfigService {
    $this->items_per_page = $items_per_page;

    return $this;
  }

}
