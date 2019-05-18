<?php

namespace Drupal\key_auth;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\user\UserInterface;
use Drupal\Component\Utility\Crypt;

/**
 * Class KeyAuth.
 *
 * Handles all functionality regarding key authentication.
 */
class KeyAuth implements KeyAuthInterface {

  /**
   * Key detection method: Header.
   *
   * @var string
   */
  const DETECTION_METHOD_HEADER = 'header';

  /**
   * Key detection method: Query.
   *
   * @var string
   */
  const DETECTION_METHOD_QUERY = 'query';

  /**
   * The module configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructs a new KeyAuth object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->config = $config_factory->get('key_auth.settings');
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getKey(Request $request) {
    // Extract the configured detection methods.
    $methods = $this->config->get('detection_methods');

    // Extract the paramater name.
    $param_name = $this->config->get('param_name');

    // Check if header detection is enabled.
    if (in_array(self::DETECTION_METHOD_HEADER, $methods)) {
      if ($key = $request->headers->get($param_name)) {
        return $key;
      }
    }

    // Check if query detection is enabled.
    if (in_array(self::DETECTION_METHOD_QUERY, $methods)) {
      if ($key = $request->query->get($param_name)) {
        return $key;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserByKey($key) {
    // Load user storage.
    $storage = $this->entityTypeManager
      ->getStorage('user');

    // Query to find a user with this key.
    $user = $storage
      ->getQuery()
      ->condition('api_key', $key)
      ->execute();

    // Check if a user was found.
    if ($user) {
      // Load and return the user.
      return $storage->load(reset($user));
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function access(UserInterface $user) {
    return $user->hasPermission('use key authentication');
  }

  /**
   * {@inheritdoc}
   */
  public function generateKey() {
    // Determine the key length.
    $length = $this->config->get('key_length');

    // Load user storage.
    $storage = $this->entityTypeManager
      ->getStorage('user');

    do {
      // Generate a key.
      $key = substr(bin2hex(Crypt::randomBytes($length)), 0, $length);

      // Query to see if this key is in use.
      $in_use = $storage
        ->getQuery()
        ->condition('api_key', $key)
        ->execute();
    } while ($in_use);

    return $key;
  }

}
