<?php

namespace Drupal\advertising_products_tipser\Plugin\AdvertisingProducts\Provider;

use Drupal\advertising_products\AdvertisingProductsProviderBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\tipser_client\TipserClient;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\Unicode;

/**
 * Provides advertising products provider plugin for Tipser.
 *
 * @AdvertisingProductsProvider(
 *   id = "tipser_provider",
 *   name = @Translation("tipser product provider")
 * )
 */
class TipserProvider extends AdvertisingProductsProviderBase {
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
  public static $productBundle = 'advertising_product_tipser';

  /**
   * @var \Drupal\tipser_client\TipserClient
   */
  protected $TipserClientService;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entityManager, TipserClient $tipserclient) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entityManager);
    $this->TipserClientService = $tipserclient;
    $client_config = \Drupal::config('tipser_client.config');
    $tipser_api = $client_config->get('tipser_api');
    $api_host = parse_url($tipser_api, PHP_URL_HOST);
    $shop_url = $client_config->get('shop_url');
    $shop_host = parse_url($shop_url, PHP_URL_HOST);
    self::$providerDomains = [$shop_host, $api_host];
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
      $container->get('tipser_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function searchProduct($input) {
  }

  /**
   * {@inheritdoc}
   */
  public function getProductIdFromUrl($uri) {
    if (true !== (bool)\Drupal::config('tipser_client.config')->get('tipser_activated')) {
      return false;
    }
    $parsedUrl = UrlHelper::parse($uri);
    // Extract product ID
    // the URLs should match two cases:
    $path = $parsedUrl['path'];
    if (
      $path &&
      (($path_parts = explode('/', $path)) && ($productId = array_pop($path_parts)))
    )
    {
      if (strlen($productId) == 24) {
        return $productId;
      }
      else if (strpos($productId, '_') == 24) {
        $id_parts = explode('_', $productId);
        return $id_parts[0];
      }
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
      $products = $this->TipserClientService->queryProducts($query);
    }
    catch (ClientException $ex) {
      \Drupal::logger('tipser')->notice('Client exception @message', array('@message' => $ex->getMessage()));
    }

    if (isset( $products) && is_array($products)) {
      return reset($products);
    }
    return FALSE;
  }


  public function getImagePrefix($product_data) {
    $parts = [
      'tipserproduct',
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

      /** @var AdvertisingProduct $product */
      $product = $this->entityManager->getStorage('advertising_product')->load($entity_id);
    }
    else {
      // Create new product entity
      $item['type'] = $this::$productBundle;
      $item['product_provider'] = $this->getPluginId();
      $item['product_id'] = $product_data['id'];

      /** @var AdvertisingProduct $product */
      $product = $this->entityManager->getStorage('advertising_product')->create($item);

      $image = $this->TipserClientService->retrieveImage($product_data);
      if ($image) {
        $file = $this->saveImage($image, $product_data);
      }
    }
    if($product_data['active']){
      if (isset($product_data['category_target_id']) && $product_data['category_target_id']
          && $product->hasField('field_category')
      ) {
        $product->get('field_category')->target_id = $product_data['category_target_id'];
      }
      $product->get('product_name')->value = Unicode::substr($product_data['name'], 0, 255);
      $product->get('product_description')->value = $product_data['description'];
      if (isset($file)) {
        $product->get('product_image')->target_id = $file->id();
        $product->get('product_image')->alt = Unicode::substr($product_data['name'], 0, 512);
      }

      $product->get('product_price')->value = $product_data['price'];
      if(!empty($product_data['cross_price'])){
        $product->get('product_original_price')->value = $product_data['cross_price'];
      }
      $product->get('product_currency')->value = $product_data['currency'];
      $product->get('product_brand')->value = isset($product_data['brand']) ? $product_data['brand'] : '';
      $product->get('product_url')->uri = $product_data['detailpageurl'];
      $product->get('product_url')->options = array();
      $product->get('product_shop')->value = Unicode::substr($product_data['merchantName'], 0, 50);
      // Published by default
      $product->get('status')->value = 1;
    }
    if (isset($product_data['available']) && $product_data['available'] != 1) {
      $product->get('product_sold_out')->value = 1;
    }
    else {
      $product->get('product_sold_out')->value = 0;
    }


    // save the product data to give other module the chance to use this data
    // in the drupal core hooks
    $product->product_data = $product_data;

    // we need to update the timestamp
    $product->changed->value = \Drupal::time()->getRequestTime();

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
