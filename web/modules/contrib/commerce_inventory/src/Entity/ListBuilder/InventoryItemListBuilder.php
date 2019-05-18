<?php

namespace Drupal\commerce_inventory\Entity\ListBuilder;

use Drupal\commerce_inventory\QuantityManagerInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of Inventory Item entities.
 *
 * @ingroup commerce_inventory
 */
class InventoryItemListBuilder extends EntityListBuilder {

  /**
   * The current location.
   *
   * @var \Drupal\commerce_inventory\Entity\InventoryLocationInterface
   */
  protected $location;

  /**
   * The Quantity Available manager.
   *
   * @var \Drupal\commerce_inventory\QuantityManagerInterface
   */
  protected $quantityAvailable;

  /**
   * The Quantity On Hand manager.
   *
   * @var \Drupal\commerce_inventory\QuantityManagerInterface
   */
  protected $quantityOnHand;

  /**
   * The entity storage class.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('current_route_match'),
      $container->get('commerce_inventory.quantity_available'),
      $container->get('commerce_inventory.quantity_on_hand')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, RouteMatchInterface $route_match, QuantityManagerInterface $quantity_available, QuantityManagerInterface $quantity_on_hand) {
    parent::__construct($entity_type, $storage);

    if ($location = $route_match->getParameter('commerce_inventory_location')) {
      $this->location = $location;
    }
    $this->quantityAvailable = $quantity_available;
    $this->quantityOnHand = $quantity_on_hand;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    if ($this->location) {
      /** @var \Drupal\commerce_inventory\Entity\Storage\InventoryItemStorageInterface $storage */
      $storage = $this->getStorage();

      $query = $storage->getItemQuery($this->location->id());

      // Only add the pager if a limit is specified.
      if ($this->limit) {
        $query->pager($this->limit);
      }
      return $query->execute();

    }
    else {
      return parent::getEntityIds();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    /** @var \Drupal\commerce_inventory\Entity\InventoryItemInterface $entity */
    $operations = parent::getDefaultOperations($entity);

    /*
     * Uncomment when seven theme fixes off-canvas styling.
     * https://www.drupal.org/project/drupal/issues/2945571
    $operations['adjust-form'] = [
      'title' => t('Adjust'),
      'weight' => -10,
      'url' => $entity->toUrl('adjust-form'),
      'attributes' => [
        'class' => ['use-ajax'],
        'data-dialog-options' => Json::encode(['width' => 500]),
        'data-dialog-renderer' => 'off_canvas',
        'data-dialog-type' => 'dialog'
      ],
    ];
    */
    $operations['canonical'] = [
      'title' => t('Overview'),
      'weight' => 0,
      'url' => $entity->toUrl('canonical'),
    ];
    $operations['adjustments'] = [
      'title' => t('Adjustments'),
      'weight' => 5,
      'url' => $entity->toUrl('adjustments'),
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
    $header['purchasable'] = $this->t('Purchasable');
    $header['quantity_on_hand'] = $this->t('On Hand');
    $header['quantity_available'] = $this->t('Available');
    $header['status'] = $this->t('Status');
    $header['valid'] = $this->t('Provider');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\commerce_inventory\Entity\InventoryItemInterface */
    $row['purchasable'] = $entity->getPurchasableEntityLabel(TRUE);
    $row['quantity_on_hand'] = $this->quantityOnHand->getQuantity($entity->id());
    $row['quantity_available'] = $this->quantityAvailable->getQuantity($entity->id());
    $row['status'] = ($entity->isActive()) ? 'Active' : 'Inactive';
    $row['valid'] = ($entity->isValid()) ? 'Valid' : 'Invalid';

    return $row + parent::buildRow($entity);
  }

}
