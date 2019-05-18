<?php

namespace Drupal\google_feeds\Plugin\views\row;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\row\RssFields;

/**
 * View row plugin to render a Google News RSS item based on fields.
 *
 * @ViewsRow(
 *   id = "google_shopping_rss_fields",
 *   title = @Translation("Google Shopping Fields"),
 *   help = @Translation("Custom RSS items for Google shopping."),
 *   theme = "views_view_row_rss_google_shopping_feed",
 *   display_types = {"feed"}
 * )
 */
class GoogleShoppingRssFields extends RssFields {

  /**
   * Does the row plugin support to add fields to it's output.
   *
   * @var bool
   */
  protected $usesFields = TRUE;

  protected $distinctValues = [];
  const AVAILABILITY_CHOICES = [
    'in stock',
    'out of stock',
    'preorder',
  ];
  const CONDITION_CHOICES = [
    'new',
    'refurbished',
    'used',
  ];

  /**
   * Define the available options.
   *
   * @return array
   *   The array with options.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['id_field'] = ['default' => ''];
    $options['title_field'] = ['default' => ''];
    $options['description_field'] = ['default' => ''];
    $options['link_field'] = ['default' => ''];
    $options['image_link_field'] = ['default' => ''];
    $options['condition_field'] = ['default' => ''];
    $options['availability_field'] = ['default' => ''];
    $options['price_field'] = ['default' => ''];
    $options['shipping_country_field'] = ['default' => ''];
    $options['shipping_service_field'] = ['default' => ''];
    $options['shipping_price_field'] = ['default' => ''];
    $options['gtin_field'] = ['default' => ''];
    $options['brand_field'] = ['default' => ''];
    $options['mpn_field'] = ['default' => ''];
    $options['google_product_category_field'] = ['default' => ''];
    $options['product_type_field'] = ['default' => ''];
    return $options;
  }

  /**
   * Options form for Google News rss feed.
   *
   * @param array $form
   *   The form to build.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state and values.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Remove the fields that are not needed for a Google News sitemap.
    unset(
      $form['title_field'],
      $form['description_field'],
      $form['creator_field'],
      $form['date_field'],
      $form['guid_field_options']
    );

    // Set the initial labels for the form fields.
    $initial_labels = ['' => $this->t('- None -')];
    $view_fields_labels = $this->displayHandler->getFieldLabels();
    $view_fields_labels = array_merge($initial_labels, $view_fields_labels);

    $form['id_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Id field'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['id_field'],
      '#required' => TRUE,
    ];
    $form['title_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Title field'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['title_field'],
      '#required' => TRUE,
    ];
    $form['description_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Description field'),
      '#description' => 'Without html',
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['description_field'],
      '#required' => TRUE,
    ];
    $form['link_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Link field'),
      '#description' => 'Absolute url',
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['link_field'],
      '#required' => TRUE,
    ];
    $form['image_link_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Image link field'),
      '#description' => 'Absolute url',
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['image_link_field'],
      '#required' => TRUE,
    ];
    $form['condition_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Condition field'),
      '#description' => implode('; ', self::CONDITION_CHOICES),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['condition_field'],
      '#required' => FALSE,
    ];
    $form['availability_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Availability field'),
      '#description' => implode('; ', self::AVAILABILITY_CHOICES),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['availability_field'],
      '#required' => TRUE,
    ];
    $form['price_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Price field'),
      '#description' => '1200.00 UAH',
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['price_field'],
      '#required' => TRUE,
    ];
    $form['shipping_country_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Shipping country field'),
      '#description' => 'UAH',
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['shipping_country_field'],
      '#required' => FALSE,
    ];
    $form['shipping_service_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Shipping service field'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['shipping_service_field'],
      '#required' => FALSE,
    ];
    $form['shipping_price_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Shipping price field'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['shipping_price_field'],
      '#required' => FALSE,
    ];
    $form['gtin_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Unique global product identifier (GTIN) field'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['gtin_field'],
      '#required' => FALSE,
    ];
    $form['brand_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Brand field'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['brand_field'],
      '#required' => TRUE,
    ];
    $form['mpn_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Mpn field'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['mpn_field'],
      '#required' => FALSE,
    ];
    $form['google_product_category_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Google product category field'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['google_product_category_field'],
      '#required' => FALSE,
    ];
    $form['product_type_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Product type field'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['product_type_field'],
      '#required' => TRUE,
    ];
  }

  /**
   * Validate the Google News RSS settings.
   *
   * @return array
   *   Array with errors, if any.
   */
  public function validate() {
    $errors = [];
    // Only title, name and date are mandatory.
    $required_options = [
      'id_field',
      'title_field',
      'description_field',
      'link_field',
      'image_link_field',
      'availability_field',
      'price_field',
      'brand_field',
      'mpn_field',
      'product_type_field',
    ];
    foreach ($required_options as $required_option) {
      if (empty($this->options[$required_option])) {
        $errors[] = $this->t('Not all required fields were filled in (Google News RSS fields).');
        break;
      }
    }
    return $errors;
  }

  /**
   * Render the RSS feed.
   *
   * @param object $row
   *   Current row to render.
   *
   * @return array
   *   Render array.
   */
  public function render($row) {
    /**
     * See:
     * https://support.google.com/news/publisher/answer/74288?hl=en
     * For required format of sitemap/feed for Google News.
     */

    static $row_index;

    // Reset the row index to zero if it has not been set.
    if (!isset($row_index)) {
      $row_index = 0;
    }

    // Create the RSS item object.
    $item = new \stdClass();

    // Add the required elements from the current row.
    $item->elements[] = [
      'key'   => 'g:id',
      'value' => $this->getField($row_index, $this->options['id_field']),
    ];
    $item->elements[] = [
      'key'   => 'g:title',
      'value' => $this->getField($row_index, $this->options['title_field']),
    ];
    $item->elements[] = [
      'key'   => 'g:description',
      'value' => $this->getField($row_index, $this->options['description_field']),
    ];
    $item->elements[] = [
      'key'   => 'g:link',
      'value' => $this->getField($row_index, $this->options['link_field']),
    ];
    $item->elements[] = [
      'key'   => 'g:image_link',
      'value' => $this->getField($row_index, $this->options['image_link_field']),
    ];
    if($this->options['condition_field']) {
      $item->elements[] = [
        'key'   => 'g:condition',
        'value' => $this->getField($row_index, $this->options['condition_field']),
      ];
    }
    $item->elements[] = [
      'key' => 'g:availability',
      'value' => $this->getField($row_index, $this->options['availability_field']),
    ];
    $item->elements[] = [
      'key'   => 'g:price',
      'value' => $this->getField($row_index, $this->options['price_field']),
    ];
    if($this->options['shipping_country_field'] || $this->options['shipping_service_field'] || $this->options['shipping_price_field']) {
      $shipping = [
        'key'   => 'g:shipping',
        'value' => '',
        'subitems' => [],
      ];
      if($this->options['shipping_country_field']) {
        $shipping['subitems'][] = [
          'key'   => 'g:country',
          'value' => $this->getField($row_index, $this->options['shipping_country_field']),
        ];
      }
      if($this->options['shipping_service_field']) {
        $shipping['subitems'][] = [
          'key'   => 'g:service',
          'value' => $this->getField($row_index, $this->options['shipping_service_field']),
        ];
      }
      if($this->options['shipping_price_field']) {
        $shipping['subitems'][] = [
          'key'   => 'g:price',
          'value' => $this->getField($row_index, $this->options['shipping_price_field']),
        ];
      }
      $item->elements[] = $shipping;
    }
    if($this->options['gtin_field']) {
      $item->elements[] = [
        'key'   => 'g:gtin',
        'value' => $this->getField($row_index, $this->options['gtin_field']),
      ];
    }
    $item->elements[] = [
      'key'   => 'g:brand',
      'value' => $this->getField($row_index, $this->options['brand_field']),
    ];
    if($this->options['mpn_field']) {
      $item->elements[] = [
        'key'   => 'g:mpn',
        'value' => $this->getField($row_index, $this->options['mpn_field']),
      ];
    }
    if(!$this->getField($row_index, $this->options['mpn_field']) && !$this->getField($row_index, $this->options['gtin_field'])) {
      $item->elements[] = [
        'key' => 'identifier_exists',
        'value' => 'false',
      ];
    }
    if($this->options['google_product_category_field'] && $this->getField($row_index, $this->options['google_product_category_field'])) {
      $item->elements[] = [
        'key' => 'g:google_product_category',
        'value' => $this->getField($row_index, $this->options['google_product_category_field']),
      ];
    }
    if($this->options['product_type_field']) {
      $item->elements[] = [
        'key'   => 'g:product_type',
        'value' => $this->getField($row_index, $this->options['product_type_field']),
      ];
    }
    // Increase the row index by one after each row.
    $row_index++;

    // Add the required namespaces.
    $this->view->style_plugin->namespaces = [
      'xmlns:g' => 'http://base.google.com/ns/1.0',
    ];

    // Create the build array and return it.
    $build = [
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#options' => $this->options,
      '#row' => $item,
      '#field_alias' => isset($this->field_alias) ? $this->field_alias : '',
    ];
    return $build;

  }

}
