<?php

namespace Drupal\pocket\Client;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use GuzzleHttp\ClientInterface;

/**
 * Client factory class.
 */
class PocketClientFactory implements PocketClientFactoryInterface {

  /**
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface
   */
  protected $storageFactory;

  /**
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuid;

  /**
   * @var string
   */
  private $key;

  /**
   * PocketClientFactory constructor.
   *
   * @param \GuzzleHttp\ClientInterface                                  $client
   * @param \Drupal\Core\Config\ConfigFactoryInterface                   $configFactory
   * @param \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface $storageFactory
   * @param \Drupal\Component\Uuid\UuidInterface                         $uuid
   */
  public function __construct(
    ClientInterface $client,
    ConfigFactoryInterface $configFactory,
    KeyValueExpirableFactoryInterface $storageFactory,
    UuidInterface $uuid
  ) {
    $this->client = $client;
    $this->configFactory = $configFactory;
    $this->storageFactory = $storageFactory;
    $this->uuid = $uuid;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthClient(): PocketAuthClient {
    return new PocketAuthClient(
      $this->client,
      $this->getKey(),
      $this->storageFactory->get('pocket'),
      $this->uuid
    );
  }

  /**
   * @param string $accessToken
   *
   * @return \Drupal\pocket\Client\PocketUserClientInterface
   */
  public function getUserClient(string $accessToken
  ): PocketUserClientInterface {
    return new PocketUserClient($this->client, $this->getKey(), $accessToken);
  }

  /**
   * {@inheritdoc}
   */
  public function hasKey(): bool {
    return $this->getKey() !== NULL;
  }

  /**
   * {@inheritdoc}
   */
  private function getKey() {
    if (!$this->key) {
      $this->key = $this->configFactory->get('pocket.config')->get('key');
    }
    return $this->key;
  }

}
