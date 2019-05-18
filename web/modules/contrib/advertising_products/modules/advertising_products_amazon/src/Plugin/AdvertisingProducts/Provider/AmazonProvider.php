<?php

namespace Drupal\advertising_products_amazon\Plugin\AdvertisingProducts\Provider;

use Drupal\advertising_products\AdvertisingProductsProviderBase;
use Drupal\advertising_products_amazon\Amazon;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides advertising products provider plugin for Amazon.
 *
 * @AdvertisingProductsProvider(
 *   id = "amazon_provider",
 *   name = @Translation("Amazon product provider")
 * )
 */
class AmazonProvider extends AdvertisingProductsProviderBase {

  /**
   * @var array
   */
  public static $providerDomains = ['www.amazon.de', 'www.amazon.com'];

  /**
   * @var string
   */
  public static $productBundle = 'advertising_product_amazon';

  /**
   * @var \Drupal\advertising_products_amazon\Amazon
   */
  protected $amazonService;

  /** @var  string */
  protected $vocabulary;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entityManager, Amazon $amazonService) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entityManager);
    $this->amazonService = $amazonService;
    $this->vocabulary = \Drupal::config('advertising_products_amazon.settings')->get('vocabulary');
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
      $container->get('advertising_products_amazon.amazon')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getProductIdFromUrl($url) {
    // Extract product ID
    if ($product_id = $this->amazonService->getAsinFromUri($url)) {
      return $product_id;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchProductOnTheFly($product_id, $entity_id = NULL) {
    // Retrieve product
    try {
      $product_request = $this->queryProduct($product_id);
    }
    catch (\Exception $e) {
    }
    if (!empty($product_request['items'])) {
      $product = reset($product_request['items']);
      if(!isset($entity_id)) {
        $entity_id = $this->getEntityIdFromProductId($product_id);
      }
      // Save product
      $fetchedProduct = FALSE;
      try {
        $fetchedProduct = $this->saveProduct($product, $entity_id);
      }
      catch (\Exception $e) {
      }
      return $fetchedProduct;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function queryProduct($product_id) {
    // Retrieve product information from API
    $response = FALSE;
    try {
      $response = $this->amazonService->itemLookup($product_id);
    }
    catch (\Exception $e) {
      throw $e;
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function searchProduct($input) {
  }

  public function getImagePrefix($product_data) {
    $parts = [
      'amazon',
      $product_data->ASIN,
      // We need a random string, to prevent browser caching for changing images.
      uniqid()
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
      $item['product_id'] = (string) $product_data->ASIN;
      $product = $this->entityManager->getStorage('advertising_product')->create($item);
    }

    /** @var \Drupal\advertising_products\Entity\AdvertisingProduct $product */
    $product->product_name->value = Unicode::substr((string)$product_data->ItemAttributes->Title, 0, 255);
    $product->product_description->value = '';
    $product->product_brand->value = Unicode::substr((string)$product_data->ItemAttributes->Brand, 0, 50);
    $product->product_url->uri = (string) $product_data->DetailPageURL;
    $product->product_url->options = array();
    $product->product_shop->value = 'Amazon';
    $product->status->value = 1;  // TODO: Try to add a 'in stock' update.

    // Set listing price from RepsonseGroup 'ItemAttributes'
    if(isset($product_data->ItemAttributes->ListPrice)) {
      $list_price = (int) $product_data->ItemAttributes->ListPrice->Amount / 100;
      $product->product_price->value = $list_price;
      $product->product_currency->value = (string) $product_data->ItemAttributes->ListPrice->CurrencyCode;
    }

    // Adjust price if we have an offer.
    if ((int)$product_data->Offers->TotalOffers > 0) {

      if (isset($product_data->Offers->Offer->OfferListing->SalePrice)) {
        $product->product_price->value = (int)$product_data->Offers->Offer->OfferListing->SalePrice->Amount / 100;
        $product->product_currency->value = (string) $product_data->Offers->Offer->OfferListing->SalePrice->CurrencyCode;
      }
      elseif(isset($product_data->Offers->Offer->OfferListing->Price)) {
        $product->product_price->value = (int) $product_data->Offers->Offer->OfferListing->Price->Amount / 100;
        $product->product_currency->value = (string) $product_data->Offers->Offer->OfferListing->Price->CurrencyCode;
      }

    }

    // check for availability
    $availability = (string) $product_data->Offers->Offer->OfferListing->Availability;
    switch ($availability) {
      case '':
      case 'Not yet released':
      case 'Not yet published ':
      case 'This item is not stocked or has been discontinued.':
      case 'Out of Stock':
      case 'Limited Availability':
      case 'Out of Print--Limited Availability':
      case 'Special Order':
      case 'This item is currently not available by this merchant':
        $product->product_sold_out->value = TRUE;
        break;
      default:
        $product->product_sold_out->value = FALSE;
        break;
    }

    if(isset($list_price)) {
      if($list_price > $product->product_price->value) {
        $product->set('product_original_price', ['value' => $list_price]);
      }
      elseif(!$product->get('product_original_price')->isEmpty()) {
        $product->set('product_original_price', []);
      }
    }

    // save the product data to give other module the chance to use this data
    // in the drupal core hooks
    $product->product_data = $product_data;

    if ($this->vocabulary && $product->hasField('field_category')) {
      $categories = $this->retrieveCategories($product_data);
      if (count($categories)) {
        $product->set('field_category', $categories);
      }
    }
    // we need to update the timestamp
    $product->changed->value = \Drupal::time()->getRequestTime();
    // we need to save the entity to get the id
    $product->save();

    // Store extra image info
    $extraImageInfo = $this->amazonService->prepareExtraImageInfo($product_data);
    $this->amazonService->storeExtraImageInfo($product->id(), $extraImageInfo);

    // Retrieve primary product image, only if is new product
    if (!$entity_id) {
      try {
        $image = $this->amazonService->retrievePrimaryImage($product_data);
      }
      catch (\Exception $e) {
        // we do not want to have products without images
        $product->delete();
        throw $e;
      }
      if ($image) {
        $file = $this->saveImage($image, $product_data);
        if ($file) {
          $product->product_image->target_id = $file->id();
          $product->product_image->alt = Unicode::substr((string)$product_data->ItemAttributes->Title, 0, 512);
          $product->save();

          return $product;
        }
        // we do not want to have products without images
        else {
          $product->delete();
        }
      }

      return FALSE;
    }

    return $product;

  }

  /**
   * {@inheritdoc}
   */
  public function updateProduct($product_id, $entity_id) {
    // Retrieve product data
    $product_request = $this->queryProduct($product_id);

    if (count($product_request['items'])) {
      // Update product entity
      $product = reset($product_request['items']);
      $this->saveProduct($product, $entity_id);
      return TRUE;
    }
    // No items returned and no lookup error
    // We assume that this means that product doesn't exist
    elseif (empty($product_request['errors']['lookup_error'])) {
      // Set product as inactive.
      $this->setProductInactive($entity_id);
      return TRUE;
    }
    // we had a lookup error
    throw new \Exception($product_request['errors']['lookup_error']);
    return FALSE;
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


  /**
   * Extract categories from amazon response and map them to Drupal terms.
   *
   * @param array $product
   *   A product array fetched from the api
   * @return array $categories
   *   Response from api
   * @throws \Exception
   */
  public function retrieveCategories($product) {
    $all_categories = [];
    if (isset($product->BrowseNodes)) {
      foreach ($product->BrowseNodes->children() as $browseNode) {
        $all_categories[] = $this->getCategories($browseNode);
      }
    }

    $term_ids = [];
    foreach ($all_categories as $category) {
      $id = $this->getTerms($category);
      if ($id) {
        $term_ids[] = $id;
      }
    }
    return $term_ids;
  }

  public function getCategories($browseNode) {
    $categories = [
      'id' => (string) $browseNode->BrowseNodeId,
      'name' => (string) $browseNode->Name,
      'parent' => '',
    ];
    if (isset($browseNode->Ancestors)) {
      $categories['parent'] = $this->getCategories($browseNode->Ancestors->BrowseNode);
    }
    return $categories;
  }

  /**
   * This function retrieves the lowest term of the BrowseNode tree
   *
   * It creates it and its ancestors if it is not found.
   */
  public function getTerms($category) {
    $term_id = 0;
    $term = advertising_products_find_term($this->vocabulary, $category['id']);
    if (!$term && isset($category['name']) && strlen($category['name']) > 0) {
      $term = Term::create(
        [
          'vid' => $this->vocabulary,
          'name' => $category['name'],
          'field_original_id' => $category['id'],
          'status' => 0,
        ]
      );
      $term->save();
      $term_id = $term->id();
      if (is_array($category['parent'])) {
        $parent_term_id = $this->getTerms($category['parent']);
        $term->set('parent', $parent_term_id);
        $term->save();
      }
    }
    else if ($term) {
      $term_id = $term->id();
    }
    return $term_id;
  }

  /**
   * {@inheritdoc}
   *
   * Save image selection
   */
  public function submitFieldWidget(array $values) {
    if ($values['image_selection_input']) {
      $product = \Drupal::entityTypeManager()->getStorage('advertising_product')->load($values['target_id']);

      $image = $this->amazonService->retrieveImage($values['image_selection_input'], $product->product_id->value);

      if ($image) {
        $poduct_data = new \stdClass();
        $poduct_data->ASIN = $product->product_id->value;

        $file = $this->saveImage($image, $poduct_data);

        if ($file) {
          $product->product_image->target_id = $file->id();
          $product->save();
        }
      }
    }
  }
}
