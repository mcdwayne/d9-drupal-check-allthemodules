<?php

namespace Drupal\advertising_products\Plugin\Queue;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Queue\DatabaseQueue as CoreDatabaseQueue;
use Drupal\advertising_products\Plugin\Queue\QueueInterface;
use Drupal\advertising_products\Plugin\Queue\QueueBase;

/**
 * A \Drupal\advertising_products\Plugin\Queue\QueueInterface compliant database backed queue.
 *
 * @AdvertisingProductsQueue(
 *   id = "adverting_products_database",
 *   label = @Translation("Database"),
 *   description = @Translation("Database backed queue for advertising_products."),
 * )
 */
class DatabaseQueue extends CoreDatabaseQueue implements QueueInterface {

  /**
   * The active Drupal database connection object.
   */
  const TABLE_NAME = 'queue';

  /**
   * @var string
   *   The queue name.
   */
  protected $queue_name = '';

  /**
   * Constructs a \Drupal\advertising_products\Plugin\Queue\Database object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The Connection object containing the key-value tables.
   */
  public function __construct($name, Connection $connection) {
    parent::__construct($name, $connection);

    $this->queue_name = 'advertising_product_' . $name . '_update';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($container->get('database'));
  }


  /**
   * {@inheritdoc}
   */
  public function createItem($data) {
    $query = $this->connection->insert(static::TABLE_NAME)
           ->fields(array(
                      'name' => $this->queue_name,
                      'data' => serialize($data),
                      'created' => time(),
                    ));
    if ($id = $query->execute()) {
      return (int) $id;
    }
    return FALSE;
  }


  /**
   * {@inheritdoc}
   */
  public function createItemMultiple(array $items) {
    $item_ids = $records = [];
    // Build a array with all exactly records as they should turn into rows.
    $time = time();
    foreach ($items as $data) {
      $records[] = [
        'name' => $this->queue_name,
        'data' => serialize($data),
        'created' => $time,
      ];
    }

    // Insert all of them using just one multi-row query.
    $query = $this->connection->insert(static::TABLE_NAME, [])->fields(['name', 'data', 'created']);
    foreach ($records as $record) {
      $query->values($record);
    }

    // Execute the query and finish the call.
    if ($id = $query->execute()) {
      $id = (int) $id;

      // A multiple row-insert doesn't give back all the individual IDs, so
      // calculate them back by applying subtraction.
      for ($i = 1; $i <= count($records); $i++) {
        $item_ids[] = $id;
        $id++;
      }
      return $item_ids;
    }
    else {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function fillQueue() {
    $entity_type = 'advertising_product';
    $query = \Drupal::database()->select($entity_type, 'adv');
    $query->fields('adv', ['id', 'product_id', 'product_provider']);
    $query->condition('adv.product_id', NULL, 'IS NOT NULL');
    $query->condition('adv.product_provider', $this->name);

    if ($this->name != 'tipser_provider') {
      $result = $query->execute()->fetchAllAssoc('id');
      $data = [];
      foreach ($result as $entity_id => $values) {
        $data[] = [
          $entity_id, // Entity ID
          $values->product_id, // ID from provider
          $this->name, // Provider
        ];
      }
      $this->createItemMultiple($data);
    }
    // we special-case tipser products
    else {
      $result_tipser = $query->execute()->fetchAllAssoc('product_id');
      $result_drupal = $query->execute()->fetchAllAssoc('id');
      $tipser_products = array_keys($result_tipser);
      $tipser_chunks = array_chunk($tipser_products, 50, TRUE);

      $state = \Drupal::state();
      $tipser_cron_last = $state->get('tipser_client.cron_last');
      $config = \Drupal::config('tipser_client.config');
      $tipser_api = $config->get('tipser_api');
      $api_host = parse_url($tipser_api, PHP_URL_HOST);
      $tipser_url = "https://$api_host/v4/export/products";
      $tipser_apikey = $config->get('tipser_apikey');

      $base_options = [];
      $base_options['header']['Accept'] = 'text/json';
      $base_options['query'] = [
        'market' => 'de',
        'apiKey' => $tipser_apikey,
      ];
      // only get items updated _after_ our timestamp
      if (is_numeric($tipser_cron_last)) {
        $base_options['query']['from'] = gmdate("Y-m-d\TH:i:s\Z", $tipser_cron_last);
      }
      // assume the best
      $success = TRUE;
      $new_timestamp = \Drupal::time()->getRequestTime();
      foreach ($tipser_chunks as $chunk) {
        $options = $base_options;
        $options['query']['productIds'] = implode(',', array_values($chunk));
        try {
          $response = \Drupal::httpClient()->get(
            $tipser_url,
            $options
          );
        }
        catch (RequestException $e) {
          // if a request has failed utterly, we need to abort. We'll try
          // again next run with the same time-offset.
          \Drupal::logger('tipser')->error('Error connecting to tipser, aborting "fill Queue": @message', ['@message' => $e->getMessage()]);
          // this may leave some extra items in the queue
          return;
        }
        $status_code = $response->getStatusCode();
        if ($status_code == 200) {
          $update_products = [];
          $data = $response->getBody()->getContents();
          $json = json_decode($data);
          foreach ($json as $idx => $product) {
            $entity_ids = $this->find_entity_ids($result_drupal, $product->id);
            foreach ($entity_ids as $entity_id) {
              if ($this->check_important_data_changes($entity_id, $product)) {
                $update_products[] = [
                  $entity_id, // Entity ID
                  $product->id, // Tipser ID
                  $this->name, // Provider
                ];
              }
            }
          }
          $this->createItemMultiple($update_products);
        }
        else {
          // if a request was rejected, we log this.
          \Drupal::logger('tipser')->error('Error message from tipser: @message, Ids: @ids', ['@message' => $status_code, '@ids' => $options['query']['productIds']]);
          // we do want to retry
          $success = FALSE;
        }
      }
      // if not all requests were successfull, we rather try again.
      if ($success) {
        $state->set('tipser_client.cron_last', $new_timestamp);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function numberOfItems() {
    $conditions = [':name' => $this->queue_name];
    return (int) $this->connection->query('SELECT COUNT(*) FROM {' . static::TABLE_NAME . '} WHERE name = :name', $conditions)
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   *
   * @todo
   *   \Drupal\Core\Queue\DatabaseQueue::claimItem() doesn't included expired
   *   items in its query which means that its essentially broken and makes our
   *   tests fail. Therefore we overload the implementation with one that does
   *   it accurately. However, this should flow back to core.
   */
  public function claimItem($lease_time = 3600) {

    // Claim an item by updating its expire fields. If claim is not successful
    // another thread may have claimed the item in the meantime. Therefore loop
    // until an item is successfully claimed or we are reasonably sure there
    // are no unclaimed items left.
    while (TRUE) {
      $conditions = [':now' => time(), ':name' => $this->queue_name];
      $item = $this->connection->queryRange('SELECT * FROM {' . static::TABLE_NAME . '} q WHERE name = :name AND ((expire = 0) OR (:now > expire)) ORDER BY created, item_id ASC', 0, 1, $conditions)->fetchObject();
      if ($item) {
        $item->item_id = (int) $item->item_id;
        $item->expire = (int) $item->expire;

        // Try to update the item. Only one thread can succeed in UPDATEing the
        // same row. We cannot rely on REQUEST_TIME because items might be
        // claimed by a single consumer which runs longer than 1 second. If we
        // continue to use REQUEST_TIME instead of the current time(), we steal
        // time from the lease, and will tend to reset items before the lease
        // should really expire.
        $update = $this->connection->update(static::TABLE_NAME)
          ->fields([
            'expire' => time() + $lease_time,
          ])
          ->condition('item_id', $item->item_id);

        // If there are affected rows, this update succeeded.
        if ($update->execute()) {
          $item->data = unserialize($item->data);
          return $item;
        }
      }
      else {
        // No items currently available to claim.
        return FALSE;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function claimItemMultiple($claims = 10, $lease_time = 3600) {
    $returned_items = $item_ids = [];

    // Retrieve all items in one query.
    $conditions = [':now' => time(), ':name' => $this->queue_name];
    $items = $this->connection->queryRange('SELECT * FROM {' . static::TABLE_NAME . '} q WHERE name = :name AND ((expire = 0) OR (:now > expire)) ORDER BY created, item_id ASC', 0, $claims, $conditions);

    // Iterate all returned items and unpack them.
    foreach ($items as $item) {
      if (!$item) continue;
      $item_ids[] = $item->item_id;
      $item->item_id = (int) $item->item_id;
      $item->expire = (int) $item->expire;
      $item->data = unserialize($item->data);
      $returned_items[] = $item;
    }

    // Update the items (marking them claimed) in one query.
    if (count($returned_items)) {
      $this->connection->update(static::TABLE_NAME)
        ->fields([
          'expire' => time() + $lease_time,
        ])
        ->condition('item_id', $item_ids, 'IN')
        ->execute();
    }

    // Return the generated items, whether its empty or not.
    return $returned_items;
  }

  /**
   * Implements \Drupal\Core\Queue\QueueInterface::releaseItem().
   */
  public function releaseItem($item) {
    return $this->connection->update(static::TABLE_NAME)
      ->fields([
        'expire' => 0,
        'data' => serialize($item->data),
      ])
      ->condition('item_id', $item->item_id)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function releaseItemMultiple(array $items) {
    // Extract item IDs and serialized data so comparing becomes easier.
    $items_data = [];
    foreach ($items as $item) {
      $items_data[intval($item->item_id)] = serialize($item->data);
    }

    // Figure out which items have changed their data and update just those.
    $originals = $this->connection
      ->select(static::TABLE_NAME, 'q')
      ->fields('q', ['item_id', 'data'])
      ->condition('item_id', array_keys($items_data), 'IN')
      ->execute();
    foreach ($originals as $original) {
      $item_id = intval($original->item_id);
      if ($original->data !== $items_data[$item_id]) {
        $this->connection->update(static::TABLE_NAME)
          ->fields(['data' => $items_data[$item_id]])
          ->condition('item_id', $item_id)
          ->execute();
      }
    }

    // Update the lease time in one single query and resolve what to return.
    $update = $this->connection->update(static::TABLE_NAME)
      ->fields(['expire' => 0])
      ->condition('item_id', array_keys($items_data), 'IN')
      ->execute();
    if ($update) {
      return [];
    }
    else {
      return $items;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteItem($item) {
    return parent::deleteItem($item);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteItemMultiple(array $items) {
    $item_ids = [];
    foreach ($items as $item) {
      $item_ids[] = $item->item_id;
    }
    $this->connection
      ->delete(static::TABLE_NAME)
      ->condition('item_id', $item_ids, 'IN')
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function createQueue() {
  }

  /**
   * {@inheritdoc}
   */
  public function deleteQueue() {
    $this->connection->delete(static::TABLE_NAME)
      ->condition('name', $this->queue_name)
      ->execute();
  }

  /**
   * Helper function to find changes to important data
   * Important are: Price in EUR and availability
   *
   * @param integer $entity_id
   *
   * @param array $tipser_product
   *
   * @return bool Update or not
   */
  protected function check_important_data_changes($entity_id, $tipser_product) {
    $local_product = \Drupal::entityManager()->getStorage('advertising_product')->load($entity_id);
    // if the availability has changed, we want to update
    if ((int) $local_product->get('product_sold_out')->value == (int) $tipser_product->isInStock) {
      return TRUE;
    }
    // if there is a discount price and it has changed, we update.
    if (isset($tipser_product->discountPriceIncVat->value)) {
      if (
        (abs($local_product->get('product_price')->value - $tipser_product->discountPriceIncVat->value) > 0.01)
        ||
        (abs($local_product->get('product_original_price')->value - $tipser_product->priceIncVat->value) > 0.01)
      ) {
        return TRUE;
      }
    }
    // else if there is a change in the normal price, we update.
    else if (abs($local_product->get('product_price')->value - $tipser_product->priceIncVat->value) > 0.01) {
      return TRUE;
    }
    // import changes to the title
    else if ($local_product->get('product_name')->value != $tipser_product->title) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Helper function to find Drupal IDs matching Provider IDs
   *
   * @param array $search
   *
   * @param string $provider_id
   *
   * @return array matches
   */
  protected function find_entity_ids($search, $product_id) {
    $ids = [];
    foreach ($search as $id => $item) {
      if ($item->product_id == $product_id) {
        $ids[] = $id;
      }
    }
    return $ids;
  }
}
