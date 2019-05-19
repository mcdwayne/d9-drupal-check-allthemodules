<?php

namespace Drupal\smugmug_api\Service;

use Drupal\Core\Logger\LoggerChannelFactory;

/**
 * Class Node.
 *
 * @package Drupal\smugmug_api\Service
 */
class Node {

  /**
   * Client.
   *
   * @var \Drupal\smugmug_api\Service\Client
   */
  protected $client;

  /**
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * Media constructor.
   *
   * @param \Drupal\smugmug_api\Service\Client $client
   *   Client.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerFactory
   *   LoggerChannelFactory.
   */
  public function __construct(Client $client,
                              LoggerChannelFactory $loggerFactory) {
    // SmugMug API Client.
    $this->client = $client;
    $this->loggerFactory = $loggerFactory;
  }

  /**
   * A node is a folder, album, or page.
   *
   * Folders contain albums, pages, and other folders,
   * and albums contain images.
   *
   * @param string $id
   *   Node ID.
   * @param bool $cacheable
   *   Cacheable.
   *
   * @return array|bool
   *   Response array.
   *   https://api.smugmug.com/api/v2/doc/reference/node.html
   *
   * @see https://api.smugmug.com/api/v2/node/XWx8t
   */
  public function getNode($id, $cacheable = TRUE) {
    $response = $this->client->request(
      'node/' . $id,
      [],
      $cacheable
    );

    if ($response) {
      return $response['Node'];
    }

    return FALSE;
  }

}
