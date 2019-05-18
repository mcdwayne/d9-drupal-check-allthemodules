<?php

namespace Drupal\advertising_products_004\Plugin\AdvertisingProducts\Provider;

use Drupal\advertising_products\AdvertisingProductsProviderBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\client_004\Client004;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\Unicode;

/**
 * Provides advertising products provider plugin for 004.
 *
 * @AdvertisingProductsProvider(
 *   id = "provider_004",
 *   name = @Translation("004 product provider")
 * )
 */
class Provider004 extends AdvertisingProductsProviderBase {
  /**
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * @var array
   */
  public static $providerDomains = [];

    /**
   * @var string
   */
  public static $productBundle = 'advertising_product_004';

  /**
   * @var \Drupal\client_004\Client004
   */
  protected $client004Service;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entityManager, Client004 $client004) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entityManager);
    $this->client004Service = $client004;
    $client_config = \Drupal::config('client_004.config');
    $shop_url = $client_config->get('shop_url');
    $host = parse_url($shop_url, PHP_URL_HOST);
    self::$providerDomains = [$host];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager'),
      $container->get('client_004')
    );
  }

    /**
   * {@inheritdoc}
   */
  public function getProductIdFromUrl($uri) {
    $parsedUrl = UrlHelper::parse($uri);
    // Extract product ID
    // the URLs should match two cases:
    // https://brandshop.004gmbh.de.dev1/app/product_widget.do?action=ShowProduct&productId=175
    // https://shop.instyle.de/InStyle-Box-Winter-2016/products/3
    $query = $parsedUrl['query'];
    $path = $parsedUrl['path'];
    if (
      ($query && isset($query['productId']) && is_numeric($query['productId']) && ($query['productId'] == floor($query['productId'])))
    )
    {
      return $query['productId'];
    }
    else if (
      ($path && ($path_parts = explode('/', $path)) && ($productId = array_pop($path_parts)) && is_numeric($productId) && ($productId == floor($productId)))
    ) {
      return $productId;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchProductOnTheFly($product_id, $entity_id = NULL) {
    // Retrieve product
    if ($product = $this->queryProduct($product_id)) {
      if(!isset($entity_id)) {
        $entity_id = $this->getEntityIdFromProductId($product_id);
      }
      // Save product
      $fetchedProduct = $this->saveProduct($product, $entity_id);
      return $fetchedProduct;
    }
    return $product;
  }

  /**
   * {@inheritdoc}
   */
  public function queryProduct($product_id) {
    $query = [
      'product_id' => $product_id
    ];

    try {
      $products = $this->client004Service->queryProducts($query);
    } catch (ClientException $ex) {}

    if (is_array($products)) {
      return reset($products);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function searchProduct($input) {
    $query = [
      'search' => $input
    ];

    $products = [];
    try {
      $products = $this->client004Service->search($query);
    } catch (ClientException $ex) {}

    if (is_array($products) && count($products)) {
      $saved_products = [];
      foreach ($products as $product) {
        $entity_id = $this->getEntityIdFromProductId($product['id']);
        $saved_products[] = $this->saveProduct($product, $entity_id);
      }
      return $saved_products;
    }
    return FALSE;
  }

  public function getImagePrefix($product_data) {
    $parts = [
      '004product',
      $product_data['id']
    ];
    return implode('-', $parts);
  }

  /**
   * {@inheritdoc}
   */
  public function saveProduct($product_data, $entity_id = NULL) {
    if ($entity_id) {
      // Update existing product entity
      $product = $this->entityManager->getStorage('advertising_product')->load($entity_id);
    }
    else {
      // Create new product entity
      $item['type'] = $this::$productBundle;
      $item['product_provider'] = $this->getPluginId();
      $item['product_id'] = $product_data['id'];
      $product = $this->entityManager->getStorage('advertising_product')->create($item);
    }
    if($product_data['active']){
      $image = $this->client004Service->retrieveImage($product_data);
      if ($image) {
        $file = $this->saveImage($image, $product_data);
      }
      $product->product_name->value = Unicode::substr($product_data['name'], 0, 255);
      $product->product_description->value = $product_data['description'];
      if ($file) {
        $product->product_image->target_id = $file->id();
        $product->product_image->alt = Unicode::substr($product_data['name'], 0, 512);
      }

      $price_replacements = ['€'=>'', '.' => '', ',' => '.'];

      $product->product_price->value = strtr($product_data['price'], $price_replacements);
      if(!empty($product_data['cross_price'])){
        $product->product_original_price->value = floatval(strtr($product_data['cross_price'], $price_replacements));
      }
      $product->product_currency->value = 'EUR';
      $product->product_brand->value = isset($product_data['brand']) ? $product_data['brand'] : '';
      $product->product_url->uri = $product_data['detailpageurl'];
      $product->product_url->options = array();
      $product->product_shop->value = '';
      // Published by default
      $product->status->value = 1;
      $product->product_sold_out->value = 0;
    }
    else {
      $product->product_sold_out->value = 1;
    }


    // save the product data to give other module the chance to use this data
    // in the drupal core hooks
    $product->product_data = $product_data;

    $product->save();
    return $product;
  }

  /**
   * {@inheritdoc}
   */
  public function updateProduct($product_id, $entity_id) {
    // Retrieve product data
    if ($product = $this->queryProduct($product_id)) {
      // Update product entity
      $this->saveProduct($product, $entity_id);
    }
    else {
      // Set product as inactive.
      $this->setProductInactive($entity_id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setProductInactive($entity_id) {
    $product = $this->entityManager->getStorage('advertising_product')->load($entity_id);
    $product->status->value = 0;
    $product->save();
  }
}
