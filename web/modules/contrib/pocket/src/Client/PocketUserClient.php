<?php

namespace Drupal\pocket\Client;

use Drupal\Core\Url;
use Drupal\pocket\Action\PocketActionInterface;
use Drupal\pocket\PocketItem;
use Drupal\pocket\PocketItemInterface;
use Drupal\pocket\PocketQuery;
use Drupal\pocket\PocketQueryInterface;
use GuzzleHttp\ClientInterface;

/**
 * Pocket user-linked client.
 */
class PocketUserClient extends PocketClient implements PocketUserClientInterface {

  /**
   * @var string
   */
  private $accessToken;

  /**
   * PocketClient constructor.
   *
   * @param \GuzzleHttp\ClientInterface $http
   * @param string                      $key
   * @param string                      $accessToken
   */
  public function __construct(ClientInterface $http, string $key, string $accessToken) {
    parent::__construct($http, $key);
    $this->accessToken = $accessToken;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \Drupal\pocket\Exception\PocketHttpException
   * @throws \Drupal\pocket\Exception\UnauthorizedException
   * @throws \Drupal\pocket\Exception\AccessDeniedException
   */
  public function add(Url $url, array $tags = [], string $title = NULL): PocketItemInterface {
    $request['url'] = $url->setAbsolute()->toString();
    if ($tags) {
      $request['tags'] = implode(',', $tags);
    }
    if ($title) {
      $request['title'] = $title;
    }
    $response = $this->sendRequest('v3/add', $request);
    return new PocketItem($response['item']);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \Drupal\pocket\Exception\PocketHttpException
   * @throws \Drupal\pocket\Exception\UnauthorizedException
   * @throws \Drupal\pocket\Exception\AccessDeniedException
   */
  public function modify(array $actions): bool {
    /** @var \Drupal\pocket\Action\PocketActionInterface[] $actions */
    $request['actions'] = [];
    foreach ($actions as $action) {
      \assert($action instanceof PocketActionInterface);
      $request['actions'][] = $action->serialize();
    }
    $response = $this->sendRequest('v3/send', $request);
    $success = !empty($response['status']);
    $results = $response['action_results'] ?? [];
    foreach ($actions as $i => $action) {
      $result = $results[$i] ?? FALSE;
      $action->setResult($result !== FALSE);
      if (\is_array($result)) {
        $action->setResultItem(new PocketItem($result));
      }
    }
    return $success;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \Drupal\pocket\Exception\PocketHttpException
   */
  public function retrieve(array $options): array {
    $response = $this->sendRequest('v3/get', $options);
    $items = [];
    foreach ($response['list'] ?? [] as $i => $item) {
      $items[$i] = new PocketItem($item);
    }
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  protected function sendRequest(string $endpoint, array $request): array {
    $request['access_token'] = $this->accessToken;
    return parent::sendRequest($endpoint, $request);
  }

  /**
   * {@inheritdoc}
   */
  public function query(array $options = []): PocketQueryInterface {
    return new PocketQuery($this, $options);
  }

}
