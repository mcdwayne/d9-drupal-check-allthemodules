<?php

namespace Drupal\Lockr;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\File\FileSystemInterface;

use Lockr\LockrSettings;

/**
 * Creates settings objects for lockr clients.
 */
class SettingsFactory {

  /** @var ConfigFactory */
  protected $configFactory;

  /** @var FileSystemInterface */
  protected $fileSystem;

  /**
   * Constructs a new settings factory.
   *
   * @param ConfigFactory
   */
  public function __construct(
    ConfigFactory $config_factory,
    FileSystemInterface $file_system
  ) {
    $this->configFactory = $config_factory;
    $this->fileSystem = $file_system;
  }

  /**
   * @return \Lockr\SettingsInterface
   */
  public function getSettings() {
    $config = $this->configFactory->get('lockr.settings');
    if ($config->get('custom')) {
      $cert_path = $this->fileSystem->realpath($config->get('cert_path'));
    }
    else {
      $partner = $this->getPartner();
      $cert_path = isset($partner['cert']) ? $partner['cert'] : NULL;
    }
    switch ($config->get('region')) {
      case 'us':
        $host = 'us.api.lockr.io';
        break;
      case 'eu':
        $host = 'eu.api.lockr.io';
        break;
      default:
        $host = 'api.lockr.io';
        break;
    }
    return new LockrSettings($cert_path, $host);
  }

  /**
   * @return array|null
   */
  public function getPartner() {
    if (defined('PANTHEON_BINDING')) {
      return [
        'name' => 'pantheon',
        'title' => 'Pantheon',
        'description' => "The Pantheor is strong with this one.
          We're detecting you 're on Pantheon and a friend of theirs is a friend of ours.
          Welcome to Lockr.",
        'cert' => '/srv/bindings/' . PANTHEON_BINDING . '/certs/binding.pem',
      ];
    }

    return NULL;
  }

}
