<?php

namespace Drupal\commerce_inventory\Entity\ListBuilder;

use Drupal\commerce_inventory\InventoryProviderManager;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of Inventory Location entities.
 *
 * @ingroup commerce_inventory
 */
class InventoryLocationListBuilder extends EntityListBuilder {

  /**
   * The Inventory Provider plugin manager.
   *
   * @var \Drupal\commerce_inventory\InventoryProviderManager
   */
  protected $providerManager;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('plugin.manager.commerce_inventory_provider')
    );
  }

  /**
   * Constructs a new EntityListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\commerce_inventory\InventoryProviderManager $provider_manager
   *   The Inventory Provider plugin manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, InventoryProviderManager $provider_manager) {
    parent::__construct($entity_type, $storage);
    $this->providerManager = $provider_manager;
  }

  protected function getDefaultOperations(EntityInterface $entity) {
    /** @var \Drupal\commerce_inventory\Entity\InventoryLocationInterface $entity */
    $operations = parent::getDefaultOperations($entity);

    $operations['canonical'] = [
      'title' => t('Overview'),
      'weight' => 0,
      'url' => $entity->toUrl('canonical'),
    ];
    $operations['inventory'] = [
      'title' => t('Inventory'),
      'weight' => -5,
      'url' => $entity->toUrl('inventory'),
    ];
    $operations['inventory-adjustments'] = [
      'title' => t('Adjustments'),
      'weight' => 1,
      'url' => $entity->toUrl('inventory-adjustments'),
    ];
    $operations['status-form'] = [
      'title' => $entity->isActive() ? t('Deactivate') : t('Activate'),
      'weight' => 20,
      'url' => $entity->toUrl('status-form'),
    ];

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Name');
    $header['provider'] = $this->t('Provider');
    $header['owner'] = $this->t('Owner');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\commerce_inventory\Entity\InventoryLocation */
    $row['name'] = $entity->toLink();
    $row['provider'] = $this->providerManager->getDefinition($entity->bundle())['label'];
    $row['owner'] = $entity->getOwner()->label();
    return $row + parent::buildRow($entity);
  }

}
