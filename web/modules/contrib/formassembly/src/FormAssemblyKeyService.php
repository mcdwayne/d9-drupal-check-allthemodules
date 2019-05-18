<?php

namespace Drupal\formassembly;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\key\Entity\Key;
use Drupal\key\KeyRepositoryInterface;

/**
 * This service retrieves the appropriate api key from the configured provider.
 *
 * Uses the Key module repository if it is available.
 */
class FormAssemblyKeyService {

  /**
   * Config for the oauth credentials.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $oauthConfig;


  /**
   * Key module service conditionally injected.
   *
   * @var \Drupal\key\KeyRepositoryInterface
   */
  protected $keyRepository;

  /**
   * KeyService constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Injected service.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->oauthConfig = $config_factory->get('formassembly.api.oauth')->get('credentials');
  }

  /**
   * Provides a means to our services.yml file to conditionally inject service.
   *
   * @param \Drupal\key\KeyRepositoryInterface $repository
   *   The injected service, if it exists.
   *
   * @see maw_luminate.services.yml
   */
  public function setKeyRepository(KeyRepositoryInterface $repository) {
    $this->keyRepository = $repository;
  }

  /**
   * Detects if key module service was injected.
   *
   * @return bool
   *   True if the KeyRepository is present.
   */
  public function additionalProviders() {
    return $this->keyRepository instanceof KeyRepositoryInterface;
  }

  /**
   * Get the oauth credentials.
   *
   * @return string
   *   The value of the configured key.
   */
  public function getOauthKeys() {
    switch ($this->oauthConfig['provider']) {
      case 'key':
        $credentials = [];
        $keyId = $this->oauthConfig['data']['id'];
        $keyEntity = $this->keyRepository->getKey($keyId);
        if ($keyEntity instanceof Key) {
          // A key was found in the repository.
          $credentials = $keyEntity->getKeyValues();
        }
        break;

      default:
        $credentials = $this->oauthConfig['data'];
    }

    return $credentials;
  }

}
