<?php

namespace Drupal\shopify\Batch;

/**
 * Class ShopifyProductBatch.
 *
 * Used for creating a product syncing batch.
 *
 * @package Drupal\shopify\Batch
 */
class ShopifyProductBatch {

  private $batch;
  private $operations;
  private $client;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->client = shopify_api_client();
  }

  /**
   * Prepares the product sync batch from passed settings.
   *
   * @param array $settings
   *   Batch specific settings. Valid values include:
   *    - num_per_batch: The number of items to sync per operation.
   *    - delete_products_first: Deletes all products from the site first.
   *    - force_udpate: Ignores last sync time and updates everything anyway.
   *
   * @return $this
   */
  public function prepare(array $settings = []) {
    $params = [];
    $params['limit'] = $settings['num_per_batch'];

    if (!$settings['force_update'] && $settings['updated_at_min'] && !$settings['delete_products_first']) {
      $params['updated_at_min'] = date(DATE_ISO8601, $settings['updated_at_min']);
    }

    $num_products = $this->client->getProductsCount();
    $num_operations = ceil($num_products / $params['limit']);

    if ($settings['delete_products_first']) {
      // Set the first operation to delete all products.
      $this->operations[] = [
        [__CLASS__, 'deleteAllProducts'],
        [
          t('Deleting all products...'),
        ],
      ];
    }

    for ($page = 1; $page <= $num_operations; $page++) {
      $params['page'] = $page;
      $this->operations[] = [
        [__CLASS__, 'operation'],
        [
          $params,
          t('(Processing page @operation)', ['@operations' => $page]),
        ],
      ];
    }

    if (!$settings['delete_products_first']) {
      // Setup operation to delete stale products.
      $this->operations[] = [
        [__CLASS__, 'cleanUpProducts'],
        [
          t('(Processing page @operation)', ['@operations' => $page]),
        ],
      ];
    }

    $this->batch = [
      'operations' => $this->operations,
      'finished' => [__CLASS__, 'finished'],
    ];

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function set() {
    batch_set($this->batch);
  }

  /**
   * {@inheritdoc}
   */
  public function getBatch() {
    return $this->batch;
  }

  /**
   * Deletes all products and variants from the database.
   */
  public static function deleteAllProducts($operation_details, &$context) {
    shopify_product_delete_all();
    $context['message'] = $operation_details;
  }

  /**
   * Deletes products on the site that don't exist on Shopify anymore.
   */
  public static function cleanUpProducts($operation_details, &$context) {
    $count = shopify_sync_deleted_products();
    if ($count) {
      drupal_set_message(t('Deleted @products.', [
        '@products' => \Drupal::translation()
          ->formatPlural($count, '@count product', '@count products'),
      ]));
      $context['message'] = $operation_details;
    }
  }

  /**
   * Product sync operation.
   *
   * TODO: Move $settings to the end.
   */
  public static function operation(array $settings = [], $operation_details, &$context) {
    $synced_products = shopify_sync_products(['query' => $settings]);
    $context['results'] = array_merge($context['results'], $synced_products);
    $context['message'] = t('Syncing @products.', [
      '@products' => \Drupal::translation()
        ->formatPlural(count($synced_products), '@count product', '@count products'),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public static function finished($success, $results, $operations) {
    // Update the product sync time.
    \Drupal::state()->set('shopify.sync.products_last_sync_time', \Drupal::time()->getRequestTime());
    drupal_set_message(t('Synced @count.', [
      '@count' => \Drupal::translation()
        ->formatPlural(count($results), '@count product', '@count products'),
    ]));
  }

}
