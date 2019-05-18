<?php

namespace Drupal\obfuscate;

/**
 * Class ObfuscateMail.
 *
 * Delegation to the chosen obfuscation method.
 *
 * @package Drupal\obfuscate
 */
class ObfuscateMail implements ObfuscateMailInterface {

  /**
   * Drupal\obfuscate\ObfuscateMailInterface definition.
   *
   * @var \Drupal\obfuscate\ObfuscateMailInterface
   */
  private $obfuscateMailMethod;

  /**
   * ObfuscateMail constructor.
   *
   * Gets the obfuscate mail method from the system wide configuration.
   */
  public function __construct() {
    $config = \Drupal::config('obfuscate.settings');
    $method = $config->get('obfuscate.method');
    $this->obfuscateMailMethod = ObfuscateMailFactory::get($method);
  }

  /**
   * {@inheritdoc}
   */
  public function getObfuscatedLink($email, array $params = []) {
    return $this->obfuscateMailMethod->getObfuscatedLink($email, $params = []);
  }

  /**
   * {@inheritdoc}
   */
  public function obfuscateEmail($email) {
    return $this->obfuscateMailMethod->obfuscateEmail($email);
  }

}
