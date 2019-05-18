<?php

namespace Drupal\advertising_products_tracdelight\Plugin\AdvertisingProducts\Provider;

use Drupal\advertising_products\AdvertisingProductsProviderBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\tracdelight_client\TracdelightClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\Unicode;

/**
 * Provides advertising products provider plugin for Tracdelight.
 *
 * @AdvertisingProductsProvider(
 *   id = "tracdelight_provider",
 *   name = @Translation("Tracdelight product provider")
 * )
 */
class TracdelightProvider extends AdvertisingProductsProviderBase {

  /**
   * @var array
   */
  public static $providerDomains = ['td.oo34.net'];

  /**
   * @var string
   */
  public static $productBundle = 'advertising_product_tracdelight';

  /**
   * @var \Drupal\tracdelight_client\TracdelightClient
   */
  protected $tracdelightService;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entityManager, TracdelightClient $tracdelight) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entityManager);
    $this->tracdelightService = $tracdelight;
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
      $container->get('tracdelight_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getProductIdFromUrl($url) {
    // Extract product ID
    if ($product_id = $this->tracdelightService->getEinFromUri($url)) {
      return $product_id;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchProductOnTheFly($product_id, $entity_id = NULL) {
    // Retrieve product
    if(!isset($entity_id)){
      $entity_id = $this->getEntityIdFromProductId($product_id);
    }
    try {
      $product = $this->queryProduct($product_id);
      // Save product
      if ($product) {
        $fetchedProduct = $this->saveProduct($product, $entity_id);
        return $fetchedProduct;
      }
    }
    catch (ClientException $ex) {
      \Drupal::logger('tracdelight')->notice('Client exception @message', array('@message' => $ex->getMessage()));
      $response_code = $ex->getResponse()->getStatusCode();
      // tracdelight will give a 404 response in case the product is no longer
      // available for purchase
      if ($response_code == 404) {
        $this->setProductInactive($entity_id);
      }
      else {
        throw $ex;
      }
    }
    catch (ServerException $ex) {
      \Drupal::logger('tracdelight')->notice('Server exception @message', array('@message' => $ex->getMessage()));
      throw $ex;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function queryProduct($product_id) {
    $products = FALSE;

    // Retrieve product information from API
    $query = [
      'EIN' => $product_id
    ];
    // The request will throw an exception in case the product is not
    // available, this will be dealt with in the functions calling this one.
    try {
      $products = $this->tracdelightService->queryProducts($query);
    }
    catch (Exception $ex) {
      throw $ex;
    }

    if (is_array($products)) {
      return reset($products);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function searchProduct($input) {
  }

  public function getImagePrefix($product_data) {
    $parts = [
      'tracdelight',
      $product_data['ein']
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
      $item['product_id'] = $product_data['ein'];
      $product = $this->entityManager->getStorage('advertising_product')->create($item);
      // Retrieve product image
      try {
        $image = $this->tracdelightService->retrieveImage($product_data);
        $file = $this->saveImage($image, $product_data);
        $product->product_image->target_id = $file->id();
        $product->product_image->alt = Unicode::substr($product_data['title'], 0, 512);
      }
      catch (Exception $ex) {
        throw $ex;
      }
    }

    $product->product_name->value = Unicode::substr($product_data['title'], 0, 255);
    $product->product_description->value = $product_data['description'];

    if (isset($product_data['category_target_id']) && $product_data['category_target_id']
        && $product->hasField('field_category')
    ) {
      $product->get('field_category')->target_id = $product_data['category_target_id'];
    }

    $product->product_price->value = $product_data['list_price']['current'];
    if($product_data['list_price']['old']){
      $product->product_original_price->value = $product_data['list_price']['old'];
    }

    $product->product_currency->value = $product_data['list_price']['currency'];
    $product->product_brand->value = Unicode::substr($product_data['brand'], 0, 50);
    $product->product_url->uri = $product_data['tracking'];
    $product->product_url->options = array();
    $product->product_shop->value = Unicode::substr($product_data['shop'], 0, 50);
    // Published by default
    $product->status->value = 1;

    // save the product data to give other module the chance to use this data
    // in the drupal core hooks
    $product->product_data = $product_data;

    // we need to update the timestamp
    $product->changed->value = \Drupal::time()->getRequestTime();

    // Save product entity
    $product->save();
    return $product;
  }

  /**
   * {@inheritdoc}
   */
  public function updateProduct($product_id, $entity_id) {
    // Retrieve product data
    try {
      $product = $this->queryProduct($product_id);
      // Update product entity
      $this->saveProduct($product, $entity_id);
    }
    catch (ClientException $ex) {
      \Drupal::logger('tracdelight')->notice('Client exception @message', array('@message' => $ex->getMessage()));
      $response_code = $ex->getResponse()->getStatusCode();
      // tracdelight will give a 404 response in case the product is no longer
      // available for purchase
      if ($response_code == 404) {
        $this->setProductInactive($entity_id);
      }
      else {
        throw $ex;
      }
    }
    catch (ServerException $ex) {
      \Drupal::logger('tracdelight')->notice('Server exception @message', array('@message' => $ex->getMessage()));
      throw $ex;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setProductInactive($entity_id) {
    if ($product = $this->entityManager->getStorage('advertising_product')->load($entity_id)) {
      $product->product_sold_out->value = TRUE;
      $product->save();
    }
  }

}
