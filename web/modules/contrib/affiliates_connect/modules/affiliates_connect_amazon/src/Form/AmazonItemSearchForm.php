<?php

namespace Drupal\affiliates_connect_amazon\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\affiliates_connect\AffiliatesNetworkManager;
use Drupal\affiliates_connect_amazon\AmazonLocale;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\affiliates_connect\Entity\AffiliatesProduct;

/**
 * Class AmazonItemSearchForm.
 */
class AmazonItemSearchForm extends FormBase {

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
   * The search data
   * @var array|null
   */
  protected $result;


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.affiliates_network'),
      $container->get('user.private_tempstore')
    );
  }

  /**
   * AffiliatesConnectController constructor.
   *
   * @param \Drupal\affiliates_connect\AffiliatesNetworkManager $affiliatesNetworkManager
   *   The affiliates network manager.
   */
  public function __construct(AffiliatesNetworkManager $affiliatesNetworkManager, PrivateTempStoreFactory $temp_store_factory) {
    $this->affiliatesNetworkManager = $affiliatesNetworkManager;
    $this->results = $temp_store_factory->get('amazon_search');
    $this->amazon = $this->affiliatesNetworkManager->createInstance('affiliates_connect_amazon');
    $this->amazon->setCredentials(
      $this->config('affiliates_connect_amazon.settings')->get('amazon_secret_key'),
      $this->config('affiliates_connect_amazon.settings')->get('amazon_access_key'),
      $this->config('affiliates_connect_amazon.settings')->get('amazon_associate_id'),
      $this->config('affiliates_connect_amazon.settings')->get('locale')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'affiliates_connect_amazon_search';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('affiliates_connect_amazon.settings');

    // query get params
    $query = \Drupal::request()->query->all();
    $data = $this->results->get('data');
    $keyword = $this->results->get('keyword');
    $category = $this->results->get('category');

    if (isset($query['page']) && $category != 'asin') {
      $this->amazon->setOption('ItemPage', $query['page'] + 1);
      $data = $this->amazon->itemSearch($keyword, $category)->execute()->getResults();
      $this->results->set('data', $data);
    }

    $form['container'] = [
      '#type' => 'container',
      '#attributes' => [
          'class' => ['container-inline'],
      ],
    ];

    $form['container']['category'] = [
      '#type' => 'select',
      '#options' => $this->buildCategories(),
      '#attributes' => ['class' => ['button']],
      '#empty_option' => 'Choose a Category',
      '#default_value' => $this->results->get('category'),
      '#required' => TRUE,
    ];

    $form['container']['keyword'] = [
      '#type' => 'textfield',
      '#default_value' => $this->results->get('keyword'),
      '#size' => 60,
      '#maxlength' => 60,
      '#placeholder' => 'Enter a keyword',
      '#required' => TRUE,
    ];

    $form['container']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Search'),
    ];

    if ($this->results->get('data')) {

        $total_items = (int) $this->results->get('data')->TotalResults;
        if ($total_items) {
          if ($category == 'All' && $total_items > 50) {
            $total_items = 50;
          } elseif ($total_items > 100) {
            $total_items = 100;
          }
          pager_default_initialize($total_items, 10);
        }


      if ($config->get('data_storage')) {

        $form['table'] = [
          '#type' => 'tableselect',
          '#header' => $this->getHeader(),
          '#options' => $this->buildRows(),
          '#multiple' => true,
          '#empty' => $this->t('No products found'),
        ];

        $form['pager'] = [
          '#type' => 'pager'
        ];

        $form['import'] = [
          '#type' => 'submit',
          '#name' => 'import',
          '#button_type' => 'primary',
          '#value' => $this->t('Import'),
        ];

        $form['import_all'] = [
          '#type' => 'submit',
          '#name' => 'import_all',
          '#button_type' => 'primary',
          '#value' => $this->t('Import All'),
        ];
      } else {
        $form['table'] = [
          '#type' => 'table',
          '#header' => $this->getHeader(),
          '#rows' => $this->buildRows(),
          '#multiple' => true,
          '#empty' => $this->t('No products found'),
        ];

        $form['pager'] = [
          '#type' => 'pager',
          '#quantity' => 10
        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    // query get params
    \Drupal::request()->query->set('page', 0);


    $keyword = $values['keyword'];
    $category = $values['category'];

    if (!$this->config('affiliates_connect_amazon.settings')->get('native_api')) {
      drupal_set_message($this->t('Configure Amazon native api to import data'), 'error', FALSE);
      $this->results->set('data', '');
      $this->results->set('keyword', '');
      $this->results->set('category', '');
      return $this->redirect('affiliates_connect_amazon.settings');
    }

    $button_clicked = $form_state->getTriggeringElement()['#name'];
    if ($button_clicked == 'import_all' && $category == 'asin') {
      drupal_set_message($this->t('Import All is not available for a single product.'), 'warning', TRUE);
    }
    else if ($button_clicked == 'import_all') {
      $query = [
        'category' => $category,
        'keyword' => $keyword,
      ];
      $form_state->setRedirect('affiliates_connect_amazon.batch_import', $query);
    } elseif ($button_clicked == 'import') {
      $selected_names = array_filter($values['table']);
      $this->importProducts($selected_names);
      return;
    }

    $data;
    if ($category == 'asin') {
      $this->amazon->unsetOption('SearchIndex');
      $data = $this->amazon->itemLookup($keyword)->execute()->getResults();
      $this->results->set('data', $data);
    }
    $this->results->set('keyword', $keyword);
    $this->results->set('category', $category);

  }

  /**
   * Add the header to the table
   * @return array header fields
   */
  public function getHeader()
  {
    $header = [
     'image' => $this->t('Image'),
     'name' => $this->t('Product Name'),
     'mrp' => $this->t('M.R.P'),
     'sellingprice' => $this->t('Selling Price'),
   ];
   return $header;
  }
  /**
   * Build the rows for the table
   * @return array fetched data from the API
   */
  public function buildRows()
  {
    $row = [];
    $data = $this->results->get('data');
    foreach ($data->Items as $key => $item) {
      $row[$key+1] = [
        'image' => [
          'data' => [
            '#prefix' => '<div><img src="' . $item->getImage('SmallImage')->URL . '" width=30 height=40> &nbsp;&nbsp;',
            '#suffix' => '</div>',
          ],
        ],
        'name' => [
          'data' => [
            '#prefix' => '<a href="' . $item->URL . '">' . $item->Title . '</a>'
          ],
        ],
        'mrp' => ($item->getPrice()) ? $item->getCurrency() . $item->getPrice() : '-',
        'sellingprice' => ($item->getSellingPrice()) ? $item->getCurrency() . $item->getSellingPrice() : '-',
      ];
    }
    return $row;
  }

  /**
   * Build the Categories on the basis of locale.
   * @return array
   */
  public function buildCategories() {
    $locale = $this->config('affiliates_connect_amazon.settings')->get('locale');
    $categories = AmazonLocale::getCategories($locale);
    $categories['asin'] = 'Search by ASIN No.';
    return $categories;
  }

  /**
   * Save data to the Product Entity.
   * @param  array $element Seleted table rows.
   */
  public function importProducts($element)
  {
    $config = \Drupal::configFactory()->get('affiliates_connect_amazon.settings');
    $data = $this->results->get('data');
    foreach ($element as $value) {
      $product = $this->buildImportData($data->Items[$value - 1]);
      AffiliatesProduct::createOrUpdate($product, $config);
    }
    drupal_set_message($this->t('Products are imported successfully'), 'status', FALSE);
  }

  /**
   * Create the array with appropriate data.
   * @param   /Drupal/affiliates_connect_amazon/AmazonItems $product_data
   * @return array
   */
  public function buildImportData($product_data) {
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
