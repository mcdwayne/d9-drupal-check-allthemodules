<?php

namespace Drupal\pocket\Client;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Core\Url;
use Drupal\pocket\AccessToken;
use GuzzleHttp\ClientInterface;

class PocketAuthClient extends PocketClient implements PocketAuthClientInterface {

  /**
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $storage;

  /**
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuid;

  /**
   * PocketAuthClient constructor.
   *
   * @param \GuzzleHttp\ClientInterface                       $http
   * @param string                                            $key
   * @param \Drupal\Core\KeyValueStore\KeyValueStoreInterface $storage
   * @param \Drupal\Component\Uuid\UuidInterface              $uuid
   */
  public function __construct(
    ClientInterface $http,
    string $key,
    KeyValueStoreInterface $storage,
    UuidInterface $uuid
  ) {
    parent::__construct($http, $key);
    $this->storage = $storage;
    $this->uuid = $uuid;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \Drupal\pocket\Exception\PocketHttpException
   * @throws \Drupal\pocket\Exception\UnauthorizedException
   * @throws \Drupal\pocket\Exception\AccessDeniedException
   * @throws \InvalidArgumentException
   */
  public function authorize(callable $callback, array $state = []): Url {
    $id = $this->uuid->generate();
    $redirect = new Url('pocket.authorize', ['id' => $id]);
    $token = $this->getRequestToken($redirect, $id);
    $this->storage->set(
      "request:$id",
      [
        'token'    => $token,
        'callback' => $callback,
        'state'    => $state,
      ]
    );

    return Url::fromUri(
      static::URL . 'auth/authorize',
      [
        'query' => [
          'request_token' => $token,
          'redirect_uri'  => $redirect->setAbsolute()->toString(),
        ],
      ]
    );
  }

  /**
   * {@inheritdoc}
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \Drupal\pocket\Exception\PocketHttpException
   * @throws \Drupal\pocket\Exception\UnauthorizedException
   * @throws \Drupal\pocket\Exception\AccessDeniedException
   */
  public function getRequestToken(Url $redirect, string $state = NULL): string {
    $request = ['redirect_uri' => $redirect->setAbsolute()->toString()];
    if ($state) {
      $request['state'] = $state;
    }
    $response = $this->sendRequest('v3/oauth/request', $request);
    return $response['code'];
  }

  /**
   * {@inheritdoc}
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \Drupal\pocket\Exception\PocketHttpException
   * @throws \Drupal\pocket\Exception\UnauthorizedException
   * @throws \Drupal\pocket\Exception\AccessDeniedException
   */
  public function getAccessToken(string $requestToken): AccessToken {
    $request = ['code' => $requestToken];
    $response = $this->sendRequest('v3/oauth/authorize', $request);
    return new AccessToken($response['access_token'], $response['username']);
  }

}
