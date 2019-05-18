<?php
/**
 * @file
 * Contains \Drupal\collect_client\CollectItem.
 */

namespace Drupal\collect_client;

/**
 * Class CollectItem
 *
 * The data transfer object sent to the collect service.
 *
 * @package Drupal\collect_client
 */
class CollectItem {

  public $uri;

  public $schema_uri;

  public $type;

  public $data;

  public $date;

  public $cache_key;
}
