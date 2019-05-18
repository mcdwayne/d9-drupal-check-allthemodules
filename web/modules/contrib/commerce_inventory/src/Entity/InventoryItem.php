<?php

namespace Drupal\commerce_inventory\Entity;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Session\AccountInterface;
use Drupal\core_extend\Entity\EntityActiveTrait;
use Drupal\core_extend\Entity\EntityCreatedTrait;

/**
 * Defines the Inventory Item entity.
 *
 * @ingroup commerce_inventory
 *
 * @ContentEntityType(
 *   id = "commerce_inventory_item",
 *   label = @Translation("Inventory Item"),
 *   label_collection = @Translation("Inventory Items"),
 *   label_singular = @Translation("inventory item"),
 *   label_plural = @Translation("inventory items"),
 *   label_count = @PluralTranslation(
 *     singular = "@count inventory item",
 *     plural = "@count inventory items"
 *   ),
 *   bundle_label = @Translation("Inventory Item type"),
 *   bundle_plugin_type = "commerce_inventory_provider",
 *   handlers = {
 *     "storage" = "Drupal\commerce_inventory\Entity\Storage\InventoryItemStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\commerce_inventory\Entity\ListBuilder\InventoryItemListBuilder",
 *     "views_data" = "Drupal\commerce_inventory\Entity\ViewsData\InventoryItemViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\commerce_inventory\Form\InventoryItemForm",
 *       "edit" = "Drupal\commerce_inventory\Form\InventoryItemForm",
 *       "adjust" = "Drupal\commerce_inventory\Form\InventoryItemAdjustForm",
 *       "status" = "Drupal\core_extend\Form\ContentEntityStatusForm",
 *       "delete" = "Drupal\commerce_inventory\Form\InventoryItemDeleteForm",
 *     },
 *     "inline_form" = "Drupal\commerce_inventory\Form\InventoryItemInlineForm",
 *     "access" = "Drupal\commerce_inventory\Entity\Access\InventoryItemAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\commerce_inventory\Entity\Routing\InventoryItemHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "commerce_inventory_item",
 *   admin_permission = "administer commerce inventory item",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "commerce_inventory_provider",
 *     "uuid" = "uuid",
 *     "status" = "status",
 *   },
 *   links = {
 *     "collection" = "/admin/commerce/config/inventory/item",
 *
 *     "canonical" = "/admin/commerce/location/{commerce_inventory_location}/inventory/{commerce_inventory_item}",
 *     "adjustments" = "/admin/commerce/location/{commerce_inventory_location}/inventory/{commerce_inventory_item}/adjustments",
 *     "adjust-form" = "/admin/commerce/location/{commerce_inventory_location}/inventory/{commerce_inventory_item}/adjustments/add",
 *     "edit-form" = "/admin/commerce/location/{commerce_inventory_location}/inventory/{commerce_inventory_item}/edit",
 *     "status-form" = "/admin/commerce/location/{commerce_inventory_location}/inventory/{commerce_inventory_item}/status",
 *     "delete-form" = "/admin/commerce/location/{commerce_inventory_location}/inventory/{commerce_inventory_item}/delete",
 *   }
 * )
 */
class InventoryItem extends ContentEntityBase implements InventoryItemInterface {

  use EntityActiveTrait;
  use EntityChangedTrait;
  use EntityCreatedTrait;

  /**
   * The Commerce Inventory cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheFactory;

  /**
   * The loaded Inventory Provider.
   *
   * @var \Drupal\commerce_inventory\Plugin\Commerce\InventoryProvider\InventoryProviderInterface
   */
  protected $inventoryProvider;

  /**
   * The quantity available manager.
   *
   * @var \Drupal\commerce_inventory\QuantityManagerInterface
   */
  protected $quantityAvailable;

  /**
   * The quantity on-hand manager.
   *
   * @var \Drupal\commerce_inventory\QuantityManagerInterface
   */
  protected $quantityOnHand;

  /**
   * Returns the Commerce Inventory cache backend.
   *
   * @return \Drupal\Core\Cache\CacheBackendInterface
   *   The Commerce Inventory cache backend.
   */
  protected function getCacheFactory() {
    if (is_null($this->cacheFactory)) {
      $this->cacheFactory = \Drupal::service('cache.commerce_inventory');
    }
    return $this->cacheFactory;
  }

  /**
   * Gets this entity's Inventory Provider instance.
   *
   * @return \Drupal\commerce_inventory\Plugin\Commerce\InventoryProvider\InventoryProviderInterface
   *   The Inventory Provider plugin instance.
   */
  protected function getProvider() {
    if (is_null($this->inventoryProvider)) {
      $this->inventoryProvider = \Drupal::service('plugin.manager.commerce_inventory_provider')->createInstance($this->bundle());;
    }
    return $this->inventoryProvider;
  }

  /**
   * Returns the quantity available manager.
   *
   * @return \Drupal\commerce_inventory\QuantityManagerInterface
   *   The quantity available manager.
   */
  protected function getQuantityAvailableManager() {
    if (is_null($this->quantityAvailable)) {
      $this->quantityAvailable = \Drupal::service('commerce_inventory.quantity_available');
    }
    return $this->quantityAvailable;
  }

  /**
   * Returns the quantity on-hand manager.
   *
   * @return \Drupal\commerce_inventory\QuantityManagerInterface
   *   The quantity on-hand manager.
   */
  protected function getQuantityOnHandManager() {
    if (is_null($this->quantityOnHand)) {
      $this->quantityOnHand = \Drupal::service('commerce_inventory.quantity_on_hand');
    }
    return $this->quantityOnHand;
  }

  /**
   * {@inheritdoc}
   */
  public function setLocationId($location_id) {
    if (is_int($location_id)) {
      $this->set('location_id', $location_id);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocationId() {
    if (!$this->get('location_id')->isEmpty()) {
      return $this->get('location_id')->target_id;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setLocation(InventoryLocationInterface $location) {
    $this->set('location_id', $location->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocation() {
    if (!$this->get('location_id')->isEmpty()) {
      return $this->get('location_id')->entity;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocationLabel($link = FALSE) {
    if ($location = $this->getLocation()) {
      if ($link) {
        return $location->toLink()->toString();
      }
      return $location->label();
    }
    return t('(Missing location)');
  }

  /**
   * {@inheritdoc}
   */
  public function getPurchasableEntityTypeId() {
    if ($this->get('purchasable_entity')->isEmpty() == FALSE) {
      return $this->get('purchasable_entity')->target_type;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getPurchasableEntityType() {
    return $this->entityTypeManager()->getDefinition($this->getPurchasableEntityTypeId(), FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function getPurchasableEntityId() {
    if ($this->get('purchasable_entity')->isEmpty() == FALSE) {
      return $this->get('purchasable_entity')->target_id;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getPurchasableEntity() {
    if ($this->get('purchasable_entity')->isEmpty() == FALSE) {
      return $this->get('purchasable_entity')->entity;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getPurchasableEntityLabel($link = FALSE) {
    if ($purchasable = $this->getPurchasableEntity()) {
      if ($link) {
        return $purchasable->toLink()->toString();
      }
      return $purchasable->label();
    }
    return t('(Missing purchasable)');
  }

  /**
   * {@inheritdoc}
   */
  public function setPurchasableEntity(PurchasableEntityInterface $purchasableEntity) {
    // Set Purchasable Entity information if it has been given an ID.
    if ($purchasableEntity->id()) {
      $this->set('purchasable_entity', $purchasableEntity);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuantity($available_only = TRUE) {
    // Exit early if id isn't set.
    if (is_null($this->id())) {
      return 0;
    }

    // Return available quantity.
    if ($available_only) {
      return $this->getQuantityAvailableManager()->getQuantity($this->id());
    }

    // Return on-hand quantity.
    return $this->getQuantityOnHandManager()->getQuantity($this->id());
  }

  /**
   * {@inheritdoc}
   */
  public function setRemoteId($remote_id, $provider_id = NULL) {
    $provider_id = is_string($provider_id) ? $provider_id : $this->bundle();
    $this->get('remote_id')->setByProvider($provider_id, $remote_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteId($provider_id = NULL) {
    $provider_id = is_string($provider_id) ? $provider_id : $this->bundle();
    return $this->get('remote_id')->getByProvider($provider_id);
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->getPurchasableEntityLabel() . ' @ ' . $this->getLocationLabel();
  }

  /**
   * {@inheritdoc}
   */
  public function isValid($validate = FALSE) {
    // Set default variables.
    $cid = (!is_null($this->id())) ? 'validation:commerce_inventory_item:' . $this->id() : NULL;

    // Get validation from cache if applicable.
    if ($validate == FALSE && !is_null($cid) && $cache = $this->getCacheFactory()->get($cid)) {
      return $cache->data;
    }

    // Catch if there is an issue connecting to the provider.
    try {
      $valid = $this->getProvider()->validateItemConfiguration($this);
    } catch (\Exception $exception) {
      // Invalidate and don't cache.
      $valid = FALSE;
      $cid = NULL;
    }

    // Set cache if applicable.
    if (!is_null($cid)) {
      $tags = Cache::mergeTags($this->getCacheTags(), [
        $cid,
        'validation:commerce_inventory_item_list',
      ]);
      $this->getCacheFactory()->set($cid, $valid, Cache::PERMANENT, $tags);
    }

    return $valid;
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);
    $uri_route_parameters['commerce_inventory_location'] = $this->getLocationId();
    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function access($operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($operation == 'create') {
      return $this->entityTypeManager()
        ->getAccessControlHandler($this->entityTypeId)
        ->createAccess($this->bundle(), $account, ['commerce_inventory_location' => $this->getLocation()], $return_as_object);
    }
    return $this->entityTypeManager()
      ->getAccessControlHandler($this->entityTypeId)
      ->access($this, $operation, $account, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTagsToInvalidate() {
    $cache_tags = [];
    if ($this->getPurchasableEntityTypeId() && $this->getPurchasableEntityId()) {
      $cache_tags[] = $this->getPurchasableEntityTypeId() . ':' . $this->getPurchasableEntityId();
    }
    if ($this->getLocationId()) {
      $cache_tags[] = 'commerce_inventory_location:' . $this->getLocationId();
    }

    return Cache::mergeTags(parent::getCacheTagsToInvalidate(), $cache_tags);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['location_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Inventory Location'))
      ->setDescription(t('The location to track inventory of this purchasable entity.'))
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setReadOnly(TRUE)
      ->addConstraint('UniquePurchasableEntity')
      ->setSetting('target_type', 'commerce_inventory_location')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['purchasable_entity'] = BaseFieldDefinition::create('dynamic_entity_reference')
      ->setLabel(t('Purchasable'))
      ->setDescription(t('The purchasable item in the inventory.'))
      ->setSetting('exclude_entity_types', FALSE)
      ->setSetting('entity_type_ids', [])
      ->setCardinality(1)
      ->setReadOnly(TRUE)
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['remote_id'] = BaseFieldDefinition::create('commerce_remote_id')
      ->setLabel(t('Remote ID'))
      ->setDescription(t('The remote ID to be used with the selected provider. Used IDs are automatically filtered out.'))
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('form', [
        'type' => 'commerce_inventory_remote_id_autocomplete',
        'weight' => 10,
        'settings' => [
          'size' => '60',
          'placeholder' => 'Start typing to find an ID.',
        ],
      ]);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Active status'))
      ->setDescription(t('A boolean indicating whether the Inventory Item is enabled.'))
      ->setDefaultValue(TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $base_field_definitions */
    /** @var \Drupal\commerce_inventory\Plugin\Commerce\InventoryProvider\InventoryProviderInterface $bundle_plugin */
    $bundle_plugin = \Drupal::service('plugin.manager.commerce_inventory_provider')->createInstance($bundle);

    $bundle_field_definitions = $bundle_plugin->bundleFieldDefinitionsAlter($entity_type, $bundle, $base_field_definitions);

    // Make sure the remote ID is required.
    if ($bundle_plugin->isItemRemoteIdRequired()) {
      if (!array_key_exists('remote_id', $bundle_field_definitions)) {
        $bundle_field_definitions['remote_id'] = $base_field_definitions['remote_id'];
      }
      $base_field_definitions['remote_id']->setRequired(TRUE);
    }

    return $bundle_field_definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    // Disable plugin on sleep so it isn't serialized.
    $this->cacheFactory = NULL;
    $this->inventoryProvider = NULL;
    $this->quantityAvailable = NULL;
    $this->quantityOnHand = NULL;
    return parent::__sleep();
  }

}
