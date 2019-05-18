<?php

namespace Drupal\affiliates_connect_amazon\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\affiliates_connect\Entity\AffiliatesProduct;
use Drupal\affiliates_connect\AffiliatesNetworkManager;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AmazonBatchImportController for Batch Importing.
 */
class AmazonBatchImportController extends ControllerBase {

  /**
   * The affiliates network manager.
   *
   * @var \Drupal\affiliates_connect\AffiliatesNetworkManager
   */
  private $affiliatesNetworkManager;

  /**
   * The Amazon Instance.
   *
   * @var \Drupal\affiliates_connect_amazon\Plugin\AffiliatesNetwork\AmazonConnect
   */
  private $amazon;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.affiliates_network')
    );
  }

  /**
   * AffiliatesConnectController constructor.
   *
   * @param \Drupal\affiliates_connect\AffiliatesNetworkManager $affiliatesNetworkManager
   *   The affiliates network manager.
   */
  public function __construct(AffiliatesNetworkManager $affiliatesNetworkManager) {
    $this->affiliatesNetworkManager = $affiliatesNetworkManager;
    $this->amazon = $this->affiliatesNetworkManager->createInstance('affiliates_connect_amazon');
    $this->amazon->setCredentials(
      $this->config('affiliates_connect_amazon.settings')->get('amazon_secret_key'),
      $this->config('affiliates_connect_amazon.settings')->get('amazon_access_key'),
      $this->config('affiliates_connect_amazon.settings')->get('amazon_associate_id'),
      $this->config('affiliates_connect_amazon.settings')->get('locale')
    );
  }

  /**
   * Start Batch Processing.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request Object
   */
  public function startBatch(Request $request)
  {
    // If enabled native_apis
    $config = $this->config('affiliates_connect_amazon.settings');
    if (!$config->get('native_api')) {
      drupal_set_message($this->t('Configure Amazon native api to import data'), 'error', FALSE);
      return $this->redirect('affiliates_connect_amazon.settings');
    }

    if (!$config->get('data_storage')) {
      drupal_set_message($this->t('Data Storage is not enabled'), 'error', FALSE);
      return $this->redirect('affiliates_connect_amazon.settings');
    }


    $params = $request->query->all();
    $category = $params['category'];
    $keyword = $params['keyword'];
    $batch = [];

    $operations = [];
    $title = '';
    $products = $this->fetchOne($keyword, $category);
    $total_pages = $products->TotalPages;

    if ($total_pages > 0) {
      // Limitation of Amazon
      if ($category == 'All' && $total_pages > 5) {
        $total_pages = 5;
      } elseif ($total_pages > 10) {
        $total_pages = 10;
      }
    } else {
      drupal_set_message($this->t('No products found to import'), 'status', FALSE);
      return;
    }
    for ($i = 1; $i <= $total_pages; $i++) {
      $operations[] = [[get_called_class(), 'startBatchImporting'], [$keyword, $category, $i]];
    }
    $title = $this->t('Importing products from @num pages', ['@num' => $total_pages]);

    $batch = [
      'title' => $title,
      'init_message' => $this->t('Importing..'),
      'operations' => $operations,
      'progressive' => TRUE,
      'finished' => [get_called_class(), 'batchFinished'],
    ];
    batch_set($batch);
    return batch_process('/admin/config/affiliates-connect/overview');
  }


  /**
   * Batch for Importing of products.
   *
   * @param string $key
   * @param string $value
   * @param $context
   */
  public static function startBatchImporting($keyword, $category, $i, &$context) {
    sleep(2);
    $categories = Self::importingProducts($keyword, $category, $i);
    $context['results']['processed']++;
    $context['message'] = 'Completed importing pages : ' . $i;
  }

  /**
   * Batch finished callback.
   *
   * @param $success
   * @param $results
   * @param $operations
   */
  public static function batchFinished($success, $results, $operations) {
    if ($success) {
     drupal_set_message(t("The products are successfully imported from Amazon."));
    }
    else {
      $error_operation = reset($operations);
      drupal_set_message(t('An error occurred while processing @operation with arguments : @args', ['@operation' => $error_operation[0], '@args' => print_r($error_operation[0], TRUE)]), 'error');
    }
  }

  /**
   * To fetch first page.
   *
   * @param string $keyword
   * @param string $category
   *
   * @return /Drupal/affiliates_connect_amazon/AmazonItems
   */
  public function fetchOne($keyword, $category) {
    $products = $this->amazon->itemSearch($keyword, $category)->execute()->getResults();
    return $products;
  }

  /**
   * To fetch first page.
   *
   * @param string $keyword
   * @param string $category
   * @param int $i
   *
   */
  public function importingProducts($keyword, $category, $i)
  {
    $amazon = \Drupal::service('plugin.manager.affiliates_network')->createInstance('affiliates_connect_amazon');
    $config = \Drupal::configFactory()->get('affiliates_connect_amazon.settings');
    $secretKey = $config->get('amazon_secret_key');
    $accessKey = $config->get('amazon_access_key');
    $associatesId = $config->get('amazon_associate_id');
    $locale = $config->get('locale');

    $amazon->setCredentials($secretKey, $accessKey, $associatesId, $locale);
    $amazon->setOption('ItemPage', $i);
    $products = $amazon->itemSearch($keyword, $category)->execute()->getResults();

    foreach ($products->Items as $key => $value) {
      $product = Self::buildImportData($value);
      AffiliatesProduct::createOrUpdate($product, $config);
    }

  }

  /**
   * To fetch first page.
   *
   * @param /Drupal/affiliates_connect_amazon/AmazonItems $product_data
   *
   * @return array
   *
   */
  public static function buildImportData($product_data) {
    $product = [
      'name' => $product_data->Title,
      'plugin_id' => 'affiliates_connect_amazon',
      'product_id' => $product_data->ASIN,
      'product_description' => '',
      'image_urls' => $product_data->getImage('SmallImage')->URL,
      'product_family' => $product_data->ProductGroup,
      'currency' => $product_data->getCurrency(),
      'maximum_retail_price' => $product_data->getPrice(),
      'vendor_selling_price' => $product_data->getSellingPrice(),
      'vendor_special_price' => $product_data->getSellingPrice(),
      'product_url' => $product_data->URL,
      'product_brand' => $product_data->Brand,
      'in_stock' => TRUE,
      'cod_available' => TRUE,
      'discount_percentage' => '',
      'product_warranty' => $product_data->Warranty,
      'offers' => '',
      'size' => $product_data->Size,
      'color' => $product_data->Color,
      'seller_name' => $product_data->Manufacturer,
      'seller_average_rating' => '',
      'additional_data' => '',
    ];
    return $product;
  }
}
