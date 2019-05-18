<?php

namespace Drupal\commerce_inventory_square\Plugin\Commerce\InventoryProvider;

use Drupal\client_connection\ClientConnectionManager;
use Drupal\commerce_inventory\Entity\InventoryAdjustmentInterface;
use Drupal\commerce_inventory\Entity\InventoryItemInterface;
use Drupal\commerce_inventory\Entity\InventoryLocationInterface;
use Drupal\commerce_inventory\InventoryHelper;
use Drupal\commerce_inventory\Plugin\Commerce\InventoryProvider\InventoryProviderBase;
use Drupal\commerce_inventory\Plugin\Commerce\InventoryProvider\InventoryProviderInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use SquareConnect\Api\LocationsApi;
use SquareConnect\Api\V1ItemsApi;
use SquareConnect\Model\V1AdjustInventoryRequest;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a default provider using Drupal storage.
 *
 * @CommerceInventoryProvider(
 *   id = "square",
 *   label = @Translation("Square"),
 *   description = @Translation("External inventory management using Square."),
 *   category = @Translation("External"),
 *   item_remote_id_required = true,
 *   location_remote_id_required = true
 * )
 */
class Square extends InventoryProviderBase implements InventoryProviderInterface {

  /**
   * Previously loaded client connections.
   *
   * @var \Drupal\client_connection_square\Plugin\ClientConnection\Square[]
   */
  protected $clientConnections = [];

  /**
   * The client connection manager.
   *
   * @var \Drupal\client_connection\ClientConnectionManager
   */
  protected $clientManager;

  /**
   * Constructs the Square object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\client_connection\ClientConnectionManager $client_manager
   *   The client connection manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, ClientConnectionManager $client_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager);

    $this->clientManager = $client_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.client_connection')
    );
  }

  /**
   * Gets a client connection for the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to use for context.
   *
   * @return \Drupal\client_connection_square\Plugin\ClientConnection\Square|null
   *   Square's Client Connection entity instance.
   */
  protected function getClientConnection(EntityInterface $entity) {
    if (!array_key_exists($entity->uuid(), $this->clientConnections)) {
      $this->clientConnections[$entity->uuid()] = $this->clientManager->resolveInstance('square', InventoryHelper::buildContexts([$entity->getEntityTypeId() => $entity]));
    }
    return $this->clientConnections[$entity->uuid()];
  }

  /**
   * Validate and retrieve data required to connect to the provider.
   *
   * @param \Drupal\commerce_inventory\Entity\InventoryItemInterface $inventory_item
   *   The Inventory Item entity.
   *
   * @return array|null
   *   An array containing the Item's provider location_id, variation_id, and
   *   resolved client connection plugin. Null if failed.
   */
  protected function initiateItemData(InventoryItemInterface $inventory_item) {
    $provider_location_id = ($inventory_item->getLocation()) ? $inventory_item->getLocation()->getRemoteId() : NULL;
    $provider_variation_id = $inventory_item->getRemoteId();

    if (is_null($provider_location_id)) {
      $this->getLogger()->error("Inventory Item ({$inventory_item->id()}) provider location ID missing.");
    }
    elseif (is_null($provider_variation_id)) {
      $this->getLogger()->error("Inventory Item ({$inventory_item->id()}) provider variation ID missing.");
    }
    elseif ($client_connection = $this->getClientConnection($inventory_item)) {
      return [
        'client_connection' => $client_connection,
        'location_id' => $provider_location_id,
        'variation_id' => $provider_variation_id,
      ];
    }
    else {
      $this->getLogger()->error("Inventory Item ({$inventory_item->id()}) client configuration lookup failed.");
    }

    return NULL;
  }

  /**
   * Make an adjustment using Square's API.
   *
   * @param \SquareConnect\Api\V1ItemsApi $api
   *   The API connection to use to adjust quantity.
   * @param string $location_id
   *   The provider location ID.
   * @param string $variation_id
   *   The provider variation ID.
   * @param float|int $quantity
   *   The amount to adjust the inventory count.
   * @param null|string $memo
   *   The memo to attach to the adjustment.
   * @param null|string $type
   *   The type of adjustment to make.
   *
   * @return float|null
   *   The quantity if adjustment completed. Null otherwise.
   */
  protected function doAdjustQuantity(V1ItemsApi $api, $location_id, $variation_id, $quantity, $memo = NULL, $type = NULL) {
    if ($api && $location_id && $variation_id) {
      $site_name = \Drupal::config('system.site')->get('name');

      // Clean variables by type.
      switch ($type) {
        case 'receive':
        case 'RECEIVE_STOCK':
          $quantity = abs($quantity);
          $type = 'RECEIVE_STOCK';
          $type_memo = 'Variation stock recieved.';
          break;

        case 'sale':
        case 'SALE':
          $quantity = abs($quantity) * -1;
          $type = 'SALE';
          $type_memo = 'Variation stock sold.';
          break;

        default:
          $type = 'MANUAL_ADJUST';
          $type_memo = 'Variation stock adjusted.';
      }

      // Clean memo.
      if (is_null($memo)) {
        $memo = $type_memo;
      }

      try {
        $provider_variation_inventory = $api->adjustInventory($location_id, $variation_id, new V1AdjustInventoryRequest([
          'adjustment_type' => $type,
          'quantity_delta' => $quantity,
          'memo' => "{$site_name}: $memo",
        ]));

        if ($provider_variation_inventory) {
          return $provider_variation_inventory->getQuantityOnHand();
        }
      }
      catch (\Exception $exception) {
        $this->getLogger()->error("Square variation ID ({$variation_id}) is not enabled to track inventory in Square.");
      }

    }
    return NULL;
  }

  /**
   * Returns an array of variation quantity data at a location.
   *
   * @param \Drupal\commerce_inventory\Entity\InventoryLocationInterface $inventory_location
   *   The Inventory Location entity.
   *
   * @return array
   *   An array of quantity counts, keyed by Square variation ID.
   */
  protected function getSquareLocationInventory(InventoryLocationInterface $inventory_location) {
    /** @var \Drupal\Core\Cache\CacheBackendInterface $cache_factory */
    $cache_factory = \Drupal::service('cache.commerce_inventory');
    $cid = "commerce_inventory_square.location:{$inventory_location->id()}:inventory";
    $inventory = [];

    if ($cache = $cache_factory->get($cid)) {
      $inventory = $cache->data;
    }
    elseif ($client_connection = $this->getClientConnection($inventory_location)) {
      $api = new V1ItemsApi($client_connection->getClient('production'));
      $batch_token = NULL;

      do {
        /** @var \SquareConnect\Model\V1InventoryEntry[] $items */
        list($items, $status_code, $header) = $api->listInventoryWithHttpInfo($inventory_location->getRemoteId(), $batch_token);

        // Add Items to full inventory list.
        if (is_array($items)) {
          foreach ($items as $item) {
            $inventory[$item->getVariationId()] = $item->getQuantityOnHand();
          }
        }

        // Set next batch token if it was supplied.
        $batch_token = NULL;
        if (is_array($header) && array_key_exists('Link', $header)) {
          parse_str(parse_url($header['Link'], PHP_URL_QUERY), $params);
          $batch_token = $params['batch_token'];
        }
      } while (!is_null($batch_token));

      // Sort results.
      ksort($inventory);

      // Cache if there are is any inventory.
      if (!empty($inventory)) {
        $cache_factory->set($cid, $inventory, REQUEST_TIME + 30, $inventory_location->getCacheTags());
      }
    }

    return $inventory;
  }

  /**
   * Returns an array of variation option data at a location.
   *
   * @param \Drupal\commerce_inventory\Entity\InventoryLocationInterface $inventory_location
   *   The Inventory Location entity.
   *
   * @return array
   *   An array of variation options, keyed by Square variation ID.
   */
  protected function getSquareLocationVariationOptions(InventoryLocationInterface $inventory_location) {
    /** @var \Drupal\Core\Cache\CacheBackendInterface $cache_factory */
    $cache_factory = \Drupal::service('cache.commerce_inventory');
    $cid = "commerce_inventory_square.location:{$inventory_location->id()}:variation_options";
    $options = [];

    if ($cache = $cache_factory->get($cid)) {
      $options = $cache->data;
    }
    elseif ($client_connection = $this->getClientConnection($inventory_location)) {
      $api = new V1ItemsApi($client_connection->getClient('production'));
      $batch_token = NULL;

      do {
        /** @var \SquareConnect\Model\V1Item[] $items */
        list($items, $status_code, $header) = $api->listItemsWithHttpInfo($inventory_location->getRemoteId(), $batch_token);

        // Add Items to options list.
        if (is_array($items)) {
          foreach ($items as $item) {
            $item_name = $item->getName();
            foreach ($item->getVariations() as $variation) {
              if ($variation->getTrackInventory()) {
                $options[$variation->getId()] = "{$item_name}: {$variation->getName()} [{$variation->getSku()}] ({$variation->getId()})";
              }
            }
          }
        }

        // Set next batch token if it was supplied.
        $batch_token = NULL;
        if (is_array($header) && array_key_exists('Link', $header)) {
          parse_str(parse_url($header['Link'], PHP_URL_QUERY), $params);
          $batch_token = $params['batch_token'];
        }
      } while (!is_null($batch_token));

      // Sort results.
      asort($options);

      // Cache if there are is any inventory.
      if (!empty($options)) {
        $cache_factory->set($cid, $options, REQUEST_TIME + 30, $inventory_location->getCacheTags());
      }
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function adjustProviderQuantity(InventoryItemInterface $inventory_item, $quantity) {
    if ($data = $this->initiateItemData($inventory_item)) {
      /** @var \Drupal\client_connection_square\Plugin\ClientConnection\Square $client_connection */
      $client_connection = $data['client_connection'];
      $provider_location_id = $data['location_id'];
      $provider_variation_id = $data['variation_id'];
      $api = new V1ItemsApi($client_connection->getClient('production'));

      $updated_quantity = $this->doAdjustQuantity($api, $provider_location_id, $provider_variation_id, $quantity);
      if (!is_null($updated_quantity)) {
        return TRUE;
      }
      $this->getLogger()->error("Inventory Item ({$inventory_item->id()}) provider quantity adjustment failed.");
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getProviderQuantity(InventoryItemInterface $inventory_item) {
    if ($inventory_item->getLocation() instanceof InventoryLocationInterface) {
      $inventory = $this->getSquareLocationInventory($inventory_item->getLocation());
      if (array_key_exists($inventory_item->getRemoteId(), $inventory)) {
        return $inventory[$inventory_item->getRemoteId()];
      }
    }

    $this->getLogger()->error("Inventory Item ({$inventory_item->id()}) provider quantity check failed.");
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function syncProviderQuantity(InventoryItemInterface $inventory_item, $update_provider_from_local = TRUE) {
    if ($data = $this->initiateItemData($inventory_item)) {
      /** @var \Drupal\client_connection_square\Plugin\ClientConnection\Square $client_connection */
      $client_connection = $data['client_connection'];
      $provider_location_id = $data['location_id'];
      $provider_variation_id = $data['variation_id'];
      $api = new V1ItemsApi($client_connection->getClient('production'));

      // Square's V1 API only allows a mass dump of a location's inventory. To
      // get around that, we use the return object from an empty Inventory
      // adjustment call to check a variations current quantity.
      $provider_quantity = $this->getProviderQuantity($inventory_item);

      if (is_null($provider_quantity)) {
        $this->getLogger()->error("Inventory Item ({$inventory_item->id()}) provider quantity check failed.");
      }
      elseif ($update_provider_from_local) {
        $on_hand_quantity = $this->getQuantityOnHandManager()->getQuantity($inventory_item->id());
        $difference_quantity = $on_hand_quantity - $provider_quantity;
        $updated_quantity = $this->doAdjustQuantity($api, $provider_location_id, $provider_variation_id, $difference_quantity, 'Variation stock sync.');
        if (!is_null($updated_quantity)) {
          return TRUE;
        }
        $this->getLogger()->error("Inventory Item ({$inventory_item->id()}) provider quantity sync failed.");
      }
      else {
        $adjustment_values['data']['skip_provider_adjustment_pre_save'] = TRUE;
        $adjustment_values['data']['skip_provider_adjustment_post_save'] = TRUE;
        $this->getInventoryAdjustmentStorage()->createAdjustment('sync', $inventory_item, $provider_quantity, $adjustment_values);
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteIdOptions($q, $entity_type_id, array $contexts = []) {
    /** @var \Drupal\Core\Plugin\Context\ContextInterface[] $contexts */
    $options = [];

    /** @var \Drupal\client_connection_square\Plugin\ClientConnection\Square $client_connection */
    $client_connection = $this->clientManager->resolveInstance('square', $contexts);
    $client = $client_connection->getClient('production');

    if ($entity_type_id == 'commerce_inventory_location') {
      $location_api = new LocationsApi($client);
      foreach ($location_api->listLocations()->getLocations() as $location) {
        $options[$location->getId()] = $location->getName() . " ({$location->getId()})";
      }
    }
    elseif ($entity_type_id == 'commerce_inventory_item') {
      /** @var \Drupal\commerce_inventory\Entity\InventoryItemInterface $inventory_item */
      $inventory_item = $contexts['commerce_inventory_item']->getContextValue();
      if ($inventory_item->getLocation() instanceof InventoryLocationInterface) {
        $options = $this->getSquareLocationVariationOptions($inventory_item->getLocation());
      }
    }

    // Filter by search.
    $q = '/(?=.*?' . str_replace(' ', ')(?=.*?', preg_quote($q)) . ')^.*$/i';
    $options = preg_grep($q, $options);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function validateRemoteId($value, $entity_type_id, array $contexts = []) {
    /** @var \Drupal\Core\Plugin\Context\ContextInterface[] $contexts */
    $options = [];

    /** @var \Drupal\client_connection_square\Plugin\ClientConnection\Square $client_connection */
    $client_connection = $this->clientManager->resolveInstance('square', $contexts);
    $client = $client_connection->getClient('production');

    if ($entity_type_id == 'commerce_inventory_location') {
      $location_api = new LocationsApi($client);
      foreach ($location_api->listLocations()->getLocations() as $location) {
        $options[$location->getId()] = $location->getName() . " ({$location->getId()})";
      }
    }
    elseif ($entity_type_id == 'commerce_inventory_item') {
      /** @var \Drupal\commerce_inventory\Entity\InventoryItemInterface $inventory_item */
      $inventory_item = $contexts['commerce_inventory_item']->getContextValue();
      if ($inventory_item->getLocation() instanceof InventoryLocationInterface) {
        $options = $this->getSquareLocationVariationOptions($inventory_item->getLocation());
      }
    }

    return array_key_exists($value, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function validateItemConfiguration(InventoryItemInterface $inventory_item) {
    return $this->validateRemoteId($inventory_item->getRemoteId(), $inventory_item->getEntityTypeId(), InventoryHelper::buildContexts([
      $inventory_item->getEntityTypeId() => $inventory_item
    ]));
  }

  /**
   * {@inheritdoc}
   */
  public function onAdjustmentPostSave(InventoryAdjustmentInterface $adjustment) {
    if ($data = $this->initiateItemData($adjustment->getItem())) {
      /** @var \Drupal\client_connection_square\Plugin\ClientConnection\Square $client_connection */
      $client_connection = $data['client_connection'];
      $provider_location_id = $data['location_id'];
      $provider_variation_id = $data['variation_id'];

      $api = new V1ItemsApi($client_connection->getClient('production'));

      $order_id = ($adjustment->hasField('order_id') && !$adjustment->get('order_id')->isEmpty()) ? $adjustment->get('order_id')->target_id : NULL;

      $context = "Adjustment: {$adjustment->id()}";
      if ($order_id) {
        $context .= ", Order: {$order_id}";
      }
      $memo = "Variation stock adjusted ({$context}).";

      // Square's V1 API only allows a mass dump of a location's inventory. To
      // get around that, we use the return object from an empty Inventory
      // adjustment call to check a variations current quantity.
      $quantity = $this->doAdjustQuantity($api, $provider_location_id, $provider_variation_id, $adjustment->getQuantity(), $memo);
      if (!is_null($quantity)) {
        return;
      }

      $this->getLogger()->error("Inventory Item ({$adjustment->getItemId()}) provider quantity adjustment failed.");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onItemPostSave(InventoryItemInterface $inventory_item, $update = TRUE) {
    if ($update == FALSE) {
      // Adjust current inventory.
      if ($quantity = $this->getProviderQuantity($inventory_item)) {
        $adjustment_values['data']['skip_provider_adjustment_pre_save'] = TRUE;
        $adjustment_values['data']['skip_provider_adjustment_post_save'] = TRUE;
        $this->getInventoryAdjustmentStorage()->createAdjustment('sync', $inventory_item, $quantity, $adjustment_values);
      }
    }
  }

}
