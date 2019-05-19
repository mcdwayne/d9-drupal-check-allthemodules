<?php

namespace Drupal\user_hash;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\user\UserDataInterface;

/**
 * Provides a User Hash services..
 */
class Services {

  /**
   * PHP hash algorithm to use.
   *
   * @var string
   */
  protected $algorithm;

  /**
   * Quantity of characters to use for the random value at hash generation.
   *
   * @var string
   */
  protected $ransdom_bytes;

  /**
   * The contact settings config object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The user data service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a ContactPageAccess instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data service.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, UserDataInterface $user_data, EntityManagerInterface $entity_manager) {
    $this->configFactory = $config_factory;
    $this->userData = $user_data;
    $this->entityManager = $entity_manager;

    $config = $config_factory->get('user_hash.settings');
    $this->algorithm = $config->get('algorithm');
    $this->random_bytes = $config->get('random_bytes');
  }

  /**
   * Generate hash.
   *
   * @param bool $raw_output
   *   When set to TRUE outputs raw binary data. FALSE outputs lowercase hexits.
   *
   * @return string
   *   Returns the generated hash.
   */
  public function generateHash($raw_output = FALSE) {
    return hash($this->algorithm, Crypt::randomBytes($this->random_bytes), $raw_output);
  }

  /**
   * @param string $username
   * @param string $hash
   * @param bool $active_only
   *
   * @return \Drupal\Core\Session\AccountInterface
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function validateHash($username, $hash, $active_only = TRUE) {
    $accounts = $this->entityManager->getStorage('user')->loadByProperties(['name' => $username, 'status' => (int) $active_only]);
    $account = reset($accounts);
    if (empty($account)) {
      return NULL;
    }
    $storedHash = $this->userData->get('user_hash', $account->id(), 'hash');
    $account->userHashIsValid = ($hash === $storedHash);
    return $account;
  }

}
