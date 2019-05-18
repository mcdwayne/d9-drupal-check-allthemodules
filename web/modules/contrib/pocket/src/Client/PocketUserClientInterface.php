<?php

namespace Drupal\pocket\Client;

use Drupal\Core\Url;
use Drupal\pocket\PocketItemInterface;
use Drupal\pocket\PocketQueryInterface;

/**
 * Pocket client interface.
 */
interface PocketUserClientInterface {

  /**
   * Perform a request on 'v3/add'.
   *
   * @param \Drupal\Core\Url $url
   *   URL of the submitted content.
   * @param string[]         $tags
   *   (optional) list of tags.
   * @param string           $title
   *   (optional) title. Ignored if the URL provides its own title.
   *
   * @return \Drupal\pocket\PocketItemInterface
   *   The item metadata returned by Pocket.
   *
   * @see https://getpocket.com/developer/docs/v3/add
   */
  public function add(Url $url, array $tags = [], string $title = NULL): PocketItemInterface;

  /**
   * Perform a request on 'v3/send'.
   *
   * Success can be checked for each action via ::isSuccessful().
   *
   * @param \Drupal\pocket\Action\PocketActionInterface[] $actions
   *   An array of actions.
   *
   * @return bool
   *   TRUE if all actions succeeded.
   *
   * @see https://getpocket.com/developer/docs/v3/modify
   */
  public function modify(array $actions): bool;

  /**
   * Perform a request on 'v3/get'.
   *
   * @param array $query
   *   An array of query parameters.
   *
   * @return \Drupal\pocket\PocketItemInterface[]
   *
   * @see https://getpocket.com/developer/docs/v3/retrieve
   */
  public function retrieve(array $query): array;

  /**
   * Build a query object for retrieving items.
   *
   * @param array $options
   *
   * @return \Drupal\pocket\PocketQueryInterface
   */
  public function query(array $options = []): PocketQueryInterface;

}
