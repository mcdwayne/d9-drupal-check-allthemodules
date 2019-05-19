<?php

namespace Drupal\virtual_entities\Plugin\VirtualEntityStorageClientPlugin;

use Drupal\virtual_entities\Plugin\VirtualEntityStorageClientPluginBase;
use GuzzleHttp\Exception\RequestException;

/**
 * Restful client.
 *
 * @VirtualEntityStorageClientPlugin(
 *   id = "virtual_entity_storage_client_plugin_restful",
 *   label = "RESTful"
 * )
 */
class Restful extends VirtualEntityStorageClientPluginBase {

  /**
   * Results.
   *
   * @var mixed
   */
  private static $results;

  /**
   * {@inheritdoc}
   */
  public function query(array $parameters = []) {
    try {
      // Load from cache.
      $cid = virtual_entities_hash($this->configuration['endpoint']);
      // Add resource type id to cache id.
      if (!empty($parameters['bundle_id'])) {
        $cid = $parameters['bundle_id'] . '-' . $cid;
      }
      if ($cache = \Drupal::cache('virtual_entities')->get($cid)) {
        self::$results = $cache->data;
      }
      else {
        $response = $this->httpClient->get($this->configuration['endpoint'], $this->configuration['httpClientParameters']);

        // Fetch data contents from remote.
        $data = $response->getBody()->getContents();
        // Use decoder to parse the data.
        $results = $this->decoder->getDecoder($this->configuration['format'])->decode($data);

        // If entities identity is set, return.
        if (!empty($this->configuration['entitiesIdentity'])) {
          $entitiesIdentity = (string) $this->configuration['entitiesIdentity'];
          // Check if this identity is available.
          if (isset($results[$entitiesIdentity])) {
            $results = $results[$entitiesIdentity];
          }
        }

        // Save entity for insert/update hooks.
        if (isset($parameters['entityType'])) {
          foreach ($results as $result) {
            $result = (object) $result;
            // Save entity to call the insert/update hooks.
            $bundle = [$parameters['entityType']->getKey('bundle') => $parameters['bundle_id']];
            $entity = \Drupal::entityTypeManager()->getStorage($parameters['entityTypeId'])->create($bundle)->mapObject($result);
            \Drupal::entityTypeManager()->getStorage($parameters['entityTypeId'])->save($entity->enforceIsNew(TRUE));
          }
        }

        // Save results.
        self::$results = (object) $results;
        // Save into cache table.
        $cid = virtual_entities_hash($this->configuration['endpoint']);
        // Add resource type id to cache id.
        if (!empty($parameters['bundle_id'])) {
          $cid = $parameters['bundle_id'] . '-' . $cid;
        }
        \Drupal::cache('virtual_entities')->set($cid, self::$results);
      }

      $results = self::$results;

      // Return page results.
      if (!empty($parameters['page_start']) && !empty($parameters['page_size'])) {
        $results = array_slice($results, $parameters['page_start'], $parameters['page_size']);
      }

      return $results;
    }
    catch (RequestException $e) {
      watchdog_exception('virtual_entities', $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function load($id) {
    // Load from cache.
    $cid = $id;
    if ($cache = \Drupal::cache('virtual_entity')->get($cid)) {
      $item = $cache->data;

      return $item;
    }
    else {
      if (empty(self::$results)) {
        // Load from cache.
        $cid = virtual_entities_hash($this->configuration['endpoint']);
        if ($cache = \Drupal::cache('virtual_entities')->get($cid)) {
          self::$results = $cache->data;
        }
        else {
          self::$results = $this->query();
        }
      }

      $items = self::$results;

      if (!empty($items)) {
        // Get entity unique ID.
        $entityUniqueId = (string) $this->configuration['entityUniqueId'];

        foreach ($items as $item) {
          // Make sure item is object.
          $item = (object) $item;
          if (isset($item->$entityUniqueId) && virtual_entities_hash($item->$entityUniqueId) == $id) {
            \Drupal::cache('virtual_entity')->set($cid, $item);

            return (object) $item;
          }
        }
      }
    }

    // Return FALSE if this entity is not available.
    return FALSE;
  }

}
