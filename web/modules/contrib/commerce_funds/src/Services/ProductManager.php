<?php

namespace Drupal\commerce_funds\Services;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_price\Price;

/**
 * Product manager class for funds products.
 */
class ProductManager {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * The db connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
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
   * Create product and its variations.
   *
   * @param string $type
   *   The type of the Product (deposit or fees).
   * @param float $amount
   *   The amount of the product type.
   * @param string $currency_code
   *   The currency code of the product type.
   *
   * @return Drupal\commerce_product\Entity\ProductVariation
   *   The product variation, deposit or fee, of the amount.
   */
  public function createProduct($type, $amount, $currency_code) {
    $title = ucfirst($type) . ' ' . $amount;
    // If deposit product already exist we load it and check for variations.
    $product_exist = $this->connection->query('SELECT product_id FROM commerce_product_field_data WHERE title = :title', [
      ':title' => $title,
    ])->fetchObject();

    // Prepare the variation for further steps.
    $sku = $type . '_' . $amount . '_' . $currency_code;
    $price = new Price($amount, $currency_code);
    $variation = [
      'type' => $type,
      'amount' => $amount,
      'currency_code' => $currency_code,
      'sku' => $sku,
      'price' => $price,
    ];

    if ($product_exist) {
      $product = Product::load($product_exist->product_id);
      // Load attached variations.
      $variation_exist = FALSE;
      $product_variations = $product->getVariations();
      // Check if the variation already exist.
      foreach ($product_variations as $product_variation) {
        $variation_sku = $product_variation->getSku();
        if ($variation_sku == $sku) {
          $variation_exist = TRUE;
          $variation_id = $product_variation->id();
          break;
        }
      }
      // Load the existing variation.
      if ($variation_exist) {
        $product_variation = ProductVariation::load($variation_id);
      }
      else {
        // Create a new variation and add it to the product.
        $product_variation = $this->createVariation($product, $variation);
      }
    }
    // Otherwhise we create a new deposit product.
    else {
      $product = Product::create([
        'type' => $type,
        'title' => ucfirst($type) . ' ' . $amount,
        'status' => 1,
      ]);
      $product->save();

      // Reload the fully product object.
      // @TODO investigate RouteMatchInterface.
      $query = 'SELECT product_id FROM commerce_product ORDER BY product_id DESC LIMIT 1';
      $product_id = $this->connection->query($query)->fetchObject()->product_id;
      $product = Product::load($product_id);

      // Create a new variation and add it to the product.
      $product_variation = $this->createVariation($product, $variation);
    }

    return $product_variation;
  }

  /**
   * Helper function to create a variation.
   *
   * @param Drupal\commerce_product\Entity\Product $product
   *   A product entity.
   * @param array $variation
   *   An associative array containing the type, amount,
   *   currency_code, sku and price.
   *
   * @see FundsProductManager::createProduct()
   *
   * @return Drupal\commerce_product\Entity\ProductVariation
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
   * @param Drupal\commerce_product\Entity\ProductVariation $product_variation
   *   The product variation of the amount.
   *
   * @return Drupal\commerce_order\Entity\Order
   *   An order object with the product variation.
   */
  public function createOrder(ProductVariation $product_variation) {
    $store = $this->entityManager->getStorage('commerce_store')->loadDefault();
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
   * @param Drupal\commerce_order\Entity\Order $order
   *   The order with a deposit amount product variation.
   * @param Drupal\commerce_product\Entity\ProductVariation $product_variation
   *   The deposit amount product variation.
   *
   * @return Drupal\commerce_order\Entity\Order
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
