<?php

namespace Drupal\commerce_inventory\Controller;

use Drupal\commerce_inventory\Entity\InventoryItemInterface;
use Drupal\commerce_inventory\Entity\InventoryLocationInterface;
use Drupal\commerce_inventory\InventoryHelper;
use Drupal\commerce_inventory\InventoryProviderManager;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Tags;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Defines a route controller for Inventory Remote ID autocomplete elements.
 */
class InventoryRemoteIdAutocompleteController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The Inventory Item entity storage.
   *
   * @var \Drupal\commerce_inventory\Entity\Storage\InventoryItemStorageInterface
   */
  protected $inventoryItemStorage;

  /**
   * The Inventory Location entity storage.
   *
   * @var \Drupal\commerce_inventory\Entity\Storage\InventoryLocationStorageInterface
   */
  protected $inventoryLocationStorage;

  /**
   * The inventory provider manager.
   *
   * @var \Drupal\commerce_inventory\InventoryProviderManager
   */
  protected $inventoryProviderManager;

  /**
   * Loaded inventory provider instances, keyed by bundle.
   *
   * @var \Drupal\commerce_inventory\Plugin\Commerce\InventoryProvider\InventoryProviderInterface[]
   */
  protected $inventoryProviders = [];

  /**
   * The key value store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $keyValue;

  /**
   * Constructs a InventoryRemoteIdAutocompleteController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_inventory\InventoryProviderManager $inventory_provider_manager
   *   The inventory provider manager.
   * @param \Drupal\Core\KeyValueStore\KeyValueStoreInterface $key_value
   *   The key value factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, InventoryProviderManager $inventory_provider_manager, KeyValueStoreInterface $key_value) {
    $this->inventoryItemStorage = $entity_type_manager->getStorage('commerce_inventory_item');
    $this->inventoryLocationStorage = $entity_type_manager->getStorage('commerce_inventory_location');
    $this->inventoryProviderManager = $inventory_provider_manager;
    $this->keyValue = $key_value;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.commerce_inventory_provider'),
      $container->get('keyvalue')->get('commerce_inventory_remote_id_autocomplete')
    );
  }

  /**
   * Loads a provider by bundle ID.
   *
   * @param string $bundle
   *   An entity's bundle ID.
   *
   * @return \Drupal\commerce_inventory\Plugin\Commerce\InventoryProvider\InventoryProviderInterface
   *   The bundle's inventory provider instance.
   */
  protected function getProvider($bundle) {
    if (!array_key_exists($bundle, $this->inventoryProviders)) {
      $this->inventoryProviders[$bundle] = $this->inventoryProviderManager->createInstance($bundle);
    }
    return $this->inventoryProviders[$bundle];
  }

  /**
   * Autocomplete the label of an entity.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object that contains the typed tags.
   * @param string $provider
   *   The ID of the provider type.
   * @param string $provider_settings_key
   *   The hashed key of the key/value entry that holds the provider handler
   *   settings.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The matched entity labels as a JSON response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown if the selection settings key is not found in the key/value store
   *   or if it does not match the stored data.
   */
  public function handleAutocomplete(Request $request, $provider, $provider_settings_key) {
    $options = [];

    // Get the typed string from the URL, if it exists.
    if ($input = $request->query->get('q')) {
      $typed_string = Tags::explode($input);
      $typed_string = Unicode::strtolower(array_pop($typed_string));

      // Provider settings are passed in as a hashed key of a serialized array
      // stored in the key/value store.
      $provider_settings = $this->keyValue->get($provider_settings_key, FALSE);
      if ($provider_settings !== FALSE) {
        $provider_settings_hash = Crypt::hmacBase64(serialize($provider_settings) . $provider, Settings::getHashSalt());
        if ($provider_settings_hash !== $provider_settings_key) {
          // Disallow access when the provider settings hash does not match the
          // passed-in key.
          throw new AccessDeniedHttpException('Invalid provider settings key.');
        }
      }
      else {
        // Disallow access when the provider settings key is not found in the
        // key/value store.
        throw new AccessDeniedHttpException();
      }

      // Disallow access when host entity not passed in the provider settings.
      if (empty($provider_settings['entity'])) {
        throw new AccessDeniedHttpException('Parent entity required.');
      }

      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      $entity = $provider_settings['entity'];
      unset($provider_settings['entity']);

      if ($this->inventoryProviderManager->hasDefinition($entity->bundle())) {
        $contexts = InventoryHelper::buildContexts([$entity->getEntityTypeId() => $entity]);
        $provider_options = $this->getProvider($entity->bundle())->getRemoteIdOptions($typed_string, $entity->getEntityTypeId(), $contexts);

        $remote_ids = [];
        if ($entity instanceof InventoryLocationInterface) {
          /** @var \Drupal\commerce_inventory\Entity\InventoryLocationInterface $entity */
          $remote_ids = $this->inventoryLocationStorage->getIdsByRemoteIds($entity->bundle(), array_keys($provider_options));
        }
        elseif ($entity instanceof InventoryItemInterface && $entity->getLocation() && $location_id = $entity->getLocation()->getRemoteId()) {
          $remote_ids = $this->inventoryItemStorage->getItemIdsByRemoteIds($entity->bundle(), $location_id, array_keys($provider_options));
        }

        // Don't filter the current ID.
        unset($remote_ids[$entity->getRemoteId()]);
        // Filter by IDs that haven't been used.
        $options = array_diff_key($provider_options, $remote_ids);

        array_walk($options, function (&$value, $id) {
          $value = ['value' => $id, 'label' => "$value"];
        });
        sort($options);
      }
    }

    return new JsonResponse($options);
  }

}
