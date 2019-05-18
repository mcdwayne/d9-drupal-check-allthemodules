<?php

namespace Drupal\redis_batch\Batch;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Batch\BatchStorageInterface;
use Drupal\redis\ClientFactory;
use Drupal\redis\RedisPrefixTrait;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * PhpRedis batch storage backend.
 */
class PhpRedisBatchStorage implements BatchStorageInterface {

  public const TTL = 864000;

  use RedisPrefixTrait;

  /**
   * The redis client factory.
   *
   * @var \Drupal\redis\ClientFactory
   */
  protected $clientFactory;

  /**
   * The CSRF token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfToken;

  /**
   * The serialization class to use.
   *
   * @var \Drupal\Component\Serialization\SerializationInterface
   */
  protected $serializer;

  /**
   * The session.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * The redis client.
   *
   * @var \Redis
   */
  private $client;

  /**
   * Constructs a PhpRedisBatchStorage object.
   *
   * @param \Drupal\redis\ClientFactory $client_factory
   *   The redis client.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrf_token
   *   The CSRF token generator.
   * @param \Drupal\Component\Serialization\SerializationInterface $serializer
   *   The serialization class to use.
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   The session.
   */
  public function __construct(
    ClientFactory $client_factory,
    CsrfTokenGenerator $csrf_token,
    SerializationInterface $serializer,
    SessionInterface $session
  ) {
    $this->clientFactory = $client_factory;
    $this->session = $session;
    $this->csrfToken = $csrf_token;
    $this->serializer = $serializer;
  }

  /**
   * {@inheritdoc}
   */
  public function load($id) {
    $this->session->start();
    $hash = $this->getClient()->hGetAll($this->getPrefix() . ':' . $id);
    if ($hash && $this->csrfToken->validate($hash['token'], $id)) {
      return $this->serializer::decode($hash['batch']);
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function create(array $batch): void {
    $this->session->start();

    $key = $this->getPrefix() . ':' . $batch['id'];
    $pipe = $this->getClient()->multi(\Redis::MULTI);
    $pipe->hMSet($key, [
      'token' => $this->csrfToken->get($batch['id']),
      'batch' => $this->serializer::encode($batch),
    ]);
    $pipe->expire($key, self::TTL);
    $pipe->exec();
  }

  /**
   * {@inheritdoc}
   */
  public function update(array $batch): array {
    $key = $this->getPrefix() . ':' . $batch['id'];
    $client = $this->getClient();
    if ($client->exists($key)) {
      $client->hSet($key, 'batch', $this->serializer::encode($batch));
    }
    return $batch;
  }

  /**
   * {@inheritdoc}
   */
  public function delete($id): void {
    $this->getClient()->del($this->getPrefix() . ':' . $id);
  }

  /**
   * {@inheritdoc}
   */
  public function cleanup(): void {
  }

  /**
   * {@inheritdoc}
   */
  protected function getPrefix(): string {
    if ($this->prefix === NULL) {
      $this->prefix = $this->getDefaultPrefix();
    }
    return $this->prefix . ':batch';
  }

  /**
   * Gets the redis client.
   *
   * @return \Redis
   *   The redis client.
   */
  private function getClient(): \Redis {
    if (!$this->client) {
      $this->client = $this->clientFactory::getClient();
    }
    return $this->client;
  }

}
