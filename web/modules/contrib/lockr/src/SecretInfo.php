<?php

namespace Drupal\lockr;

use Drupal\Core\Config\ConfigFactory;

use Drupal\key\KeyRepositoryInterface;

use Lockr\SecretInfoInterface;

class SecretInfo implements SecretInfoInterface {

  /** @var ConfigFactory */
  protected $configFactory;

  /** @var KeyRepositoryInterface */
  protected $keyRepository;

  /**
   * Constructs a new settings factory.
   *
   * @param ConfigFactory
   * @param KeyRepositoryInterface
   */
  public function __construct(ConfigFactory $config_factory, KeyRepositoryInterface $key_repository) {
    $this->configFactory = $config_factory;
    $this->keyRepository = $key_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function getSecretInfo($name) {
    $config = $this->configFactory->get('lockr.secret_info');
    $info = $config->get($name);
    if (!$info) {
      $key = $this->keyRepository->getKey($name);
      if ($key) {
        $provider = $key->getKeyProvider();
        $config = $provider->getConfiguration();
        if (isset($config['encoded'])) {
          return ['wrapping_key' => $config['encoded']];
        }
      }
    }
    return $info ?: [];
  }

  /**
   * {@inheritdoc}
   */
  public function setSecretInfo($name, array $info) {
    $config = $this->configFactory->getEditable('lockr.secret_info');
    $config->set($name, $info);
    $config->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getAllSecretInfo() {
    $config = $this->configFactory->get('lockr.secret_info');
    return $config->get();
  }

}
