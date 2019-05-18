<?php

namespace Drupal\commerce_pricelist;

use Drupal\commerce_price\Calculator;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the list builder for price list items.
 */
class PriceListItemListBuilder extends EntityListBuilder {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new PriceListItemListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity storage.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityTypeManagerInterface $entity_type_manager, RouteMatchInterface $route_match) {
    $this->entityTypeManager = $entity_type_manager;
    $this->routeMatch = $route_match;

    parent::__construct($entity_type, $entity_type_manager->getStorage('commerce_pricelist_item'));
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $price_list = $this->routeMatch->getParameter('commerce_pricelist');
    $query = $this->getStorage()->getQuery()
      ->condition('price_list_id', $price_list->id())
      ->sort('purchasable_entity')
      ->sort('quantity');
    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $price_list = $this->routeMatch->getParameter('commerce_pricelist');
    $purchasable_entity_type = $this->entityTypeManager->getDefinition($price_list->bundle());

    $header['purchasable_entity'] = $purchasable_entity_type->getLabel();
    $header['quantity'] = $this->t('Quantity');
    $header['list_price'] = $this->t('List price');
    $header['price'] = $this->t('Price');
    $header['status'] = $this->t('Status');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\commerce_pricelist\Entity\PriceListItemInterface $entity */
    $row['purchasable_entity'] = $entity->getPurchasableEntity()->getOrderItemTitle();
    $row['quantity'] = Calculator::trim($entity->getQuantity());
    $row['list_price'] = [
      'data' => [
        '#type' => 'inline_template',
        '#template' => '{{list_price|commerce_price_format|default("N/A")}}',
        '#context' => [
          'list_price' => $entity->getListPrice(),
        ],
      ],
    ];
    $row['price'] = [
      'data' => [
        '#type' => 'inline_template',
        '#template' => '{{price|commerce_price_format}}',
        '#context' => [
          'price' => $entity->getPrice(),
        ],
      ],
    ];
    $row['status'] = $entity->isEnabled() ? $this->t('Enabled') : $this->t('Disabled');

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#empty'] = $this->t('There are no prices yet.');

    return $build;
  }

}
