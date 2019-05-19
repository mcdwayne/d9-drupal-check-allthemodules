<?php

namespace Drupal\ubercart_funds\Services;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\node\Entity\Node;
use Drupal\ubercart_order\Entity\Order;
use Drupal\ubercart_order\Entity\OrderItem;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Product manager class for funds products.
 */
class ProductManager {

  /**
   * Defines variables to be used later.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   * @var \Drupal\Core\Database\Connection $connection
   */
  protected $entityManager;
  protected $connection;

  /**
   * Class constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, Connection $connection) {
    $this->entityManager = $entity_manager;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('database')
    );
  }

  /**
   * Create product.
   *
   * @param string $type
   *   The type of the Product (deposit).
   * @param float $amount
   *   The amount of the product type.
   * @param string $currency_code
   *   The currency code of the product type.
   *
   * @return Drupal\node\Entity\Node
   *   The product for ubercart.
   */
  public function createProduct($type, $amount, $currency_code) {
    $title = ucfirst($type) . ' ' . $amount;
    $sku = $type . '_' . $amount . '_' . $currency_code;
    // If deposit product already exist we load it.
    $product_exist = $this->connection->query('SELECT nid FROM uc_products WHERE model = :sku', [
      ':sku' => $sku,
    ])->fetchObject();
    if ($product_exist) {
      $deposit_node = Node::load($product_exist->nid);
    }
    // Otherwhise we create a new deposit product.
    else {
      $deposit_node = Node::create([
        'type' => $type,
        'title' => $title,
        'uid' => 1,
        'model' => $sku,
        'price' => $amount,
        'shippable' => 0,
      ]);
      $deposit_node->save();
    }

    return $deposit_node;
  }

  /**
   * Helper function to create a variation.
   *
   * @param Drupal\ubercart_product\Entity\Product $product
   *   A product entity.
   * @param array $variation
   *   An associative array containing the type, amount,
   *   currency_code, sku and price.
   *
   * @see FundsProductManager::createProduct()
   *
   * @return Drupal\ubercart_product\Entity\ProductVariation
   *   The product variation, deposit or fee, of the amount.
   */
  protected function createVariation(Product $product, array $variation) {
    $product_variation = ProductVariation::create([
      'title' => ucfirst($variation['type']) . ' ' . $variation['amount'] . ' ' . $variation['currency_code'],
      'type' => $variation['type'],
      'product_id' => $product->id(),
      'sku' => $variation['sku'],
      'price' => $variation['price'],
    ]);
    $product_variation->save();

    // Add the variation to the product.
    $product->addVariation($product_variation);

    return $product_variation;
  }

  /**
   * Create an order with a product variation.
   *
   * @param Drupal\ubercart_product\Entity\ProductVariation $product_variation
   *   The product variation of the amount.
   *
   * @return Drupal\ubercart_order\Entity\Order
   *   An order object with the product variation.
   */
  public function createOrder(ProductVariation $product_variation) {
    $store = $this->entityManager->getStorage('ubercart_store')->loadDefault();
    // Create a new order item.
    $order_item = OrderItem::create([
      'type' => 'deposit',
      'purchased_entity' => $product_variation,
      'quantity' => 1,
      'unit_price' => $product_variation->getPrice(),
    ]);
    $order_item->save();
    // Add the product variation to a new order.
    $order = Order::create([
      'type' => 'deposit',
      'order_items' => [$order_item],
      'store_id' => $store->id(),
      'checkout_flow' => 'deposit',
      'checkout_step' => 'order_information',
    ]);

    $order->save();
    // Add the deposit order item.
    $order->addItem($order_item);
    $order->save();

    return $order;
  }

  /**
   * Update an existing order with fee product.
   *
   * @param Drupal\ubercart_order\Entity\Order $order
   *   The order with a deposit amount product variation.
   * @param Drupal\ubercart_product\Entity\ProductVariation $product_variation
   *   The deposit amount product variation.
   *
   * @return Drupal\ubercart_order\Entity\Order
   *   The order with the fee product variation added.
   */
  public function updateOrder(Order $order, ProductVariation $product_variation) {
    // Get order items.
    $order_items = $order->getItems();
    // Remove previous fee product varition if exist.
    if (isset($order_items[1])) {
      $order->removeItem($order_items[1]);
    }
    // Create a new order item.
    $order_item = OrderItem::create([
      'type' => 'fee',
      'purchased_entity' => $product_variation,
      'quantity' => 1,
      'unit_price' => $product_variation->getPrice(),
    ]);
    $order_item->save();

    // Add the deposit order item.
    $order->addItem($order_item);
    $order->save();

    return $order;
  }

}
