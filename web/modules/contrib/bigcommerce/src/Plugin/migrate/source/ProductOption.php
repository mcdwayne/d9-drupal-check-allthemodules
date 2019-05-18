<?php

namespace Drupal\bigcommerce\Plugin\migrate\source;

use BigCommerce\Api\v3\ApiException;

/**
 * Gets all Product Options from BigCommerce API.
 *
 * @MigrateSource(
 *   id = "bigcommerce_product_option"
 * )
 */
class ProductOption extends BigCommerceSource {

  /**
   * {@inheritdoc}
   */
  public function getSourceResponse(array $params) {
    list($response) = $this->getProductOptions($params);
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function getYield(array $params) {
    $total_pages = 1;
    $options = [];
    while ($params['page'] < $total_pages) {
      $params['page']++;

      $response = $this->getSourceResponse($params);
      foreach ($response->getData() as $option) {
        $name = $option->getName();
        if (!in_array($name, $options, TRUE)) {
          $options[] = $name;
          yield $option->get();
        }
      }

      if ($params['page'] === 1) {
        $total_pages = $response->getMeta()->getPagination()->getTotalPages();
      }
    }
  }

  /**
   * Load Product Options from BigCommerce API.
   *
   * @param array $params
   *   (optional) Defaults to an empty array.
   *
   * @return array
   *   Contains \BigCommerce\Api\v3\Model\OptionCollectionResponse, HTTP status
   *   code, HTTP response headers (array of strings).
   *
   * @throws \BigCommerce\Api\v3\ApiException
   *   Thrown on a non-2xx response.
   * @throws \InvalidArgumentException
   */
  protected function getProductOptions(array $params = []) {

    // Parse inputs.
    $resourcePath = '/catalog/options';
    $queryParams = [];
    $headerParams = [];
    $_header_accept = $this->catalogApi->getApiClient()->selectHeaderAccept(['application/json']);
    if (!is_null($_header_accept)) {
      $headerParams['Accept'] = $_header_accept;
    }
    $headerParams['Content-Type'] = $this->catalogApi->getApiClient()->selectHeaderContentType(['application/json']);

    // Query params.
    foreach ($params as $key => $param) {
      $queryParams[$key] = $this->catalogApi->getApiClient()->getSerializer()->toQueryValue($param);
    }

    // Make the API Call.
    try {
      list($response, $statusCode, $httpHeader) = $this->catalogApi->getApiClient()->callApi(
        $resourcePath,
        'GET',
        $queryParams,
        '',
        $headerParams,
        '\BigCommerce\Api\v3\Model\OptionCollectionResponse',
        $resourcePath
      );
      return [
        $this->catalogApi->getApiClient()->getSerializer()->deserialize($response, '\BigCommerce\Api\v3\Model\OptionCollectionResponse', $httpHeader),
        $statusCode,
        $httpHeader,
      ];

    }
    catch (ApiException $e) {
      if ($e->getCode() === 200) {
        $data = $this->catalogApi->getApiClient()->getSerializer()->deserialize($e->getResponseBody(), '\BigCommerce\Api\v3\Model\OptionCollectionResponse', $e->getResponseHeaders());
        $e->setResponseObject($data);
      }
      throw $e;
    }

  }

  /**
   * Return the fields for option type to create.
   *
   * @param string $option_type
   *   The BigCommerce Option Type.
   *
   * @return array
   *   Returns the list of fields to create.
   */
  protected function getOptionFields($option_type) {
    $type = str_replace('_', '', ucwords($option_type, '_'));
    $method = 'get' . $type . 'Fields';
    if (method_exists($this, $method)) {
      return $this->$method();
    }
    return NULL;
  }

  /**
   * Return the fields for Swatch type field.
   *
   * @return array
   *   Returns the list of fields to create.
   */
  protected function getSwatchFields() {
    return [
      'color' => [
        'source' => 'value_data/colors',
        'label' => 'Color',
        'field_name' => 'field_product_attribute_colors',
        'type' => 'color_field_type',
        'required' => FALSE,
        'cardinality' => -1,
        'storage_settings' => [
          'format' => '#hexhex',
        ],
        'instance_settings' => [
          'opacity' => FALSE,
        ],
      ],
      'pattern' => [
        'source' => 'value_data/image_url',
        'process' => [
          [
            'plugin' => 'image_import',
            'source' => 'value_data/image_url',
          ],
        ],
        'label' => 'Pattern',
        'field_name' => 'field_product_attribute_pattern',
        'type' => 'image',
        'required' => FALSE,
        'cardinality' => 1,
        'storage_settings' => [
          'target_type' => 'file',
          'default_image' => [
            'uuid' => NULL,
            'alt' => NULL,
            'title' => NULL,
            'width' => NULL,
            'height' => NULL,
          ],
        ],
        'instance_settings' => [
          'file_directory' => 'bigcommerce/product-attributes/swatch-patterns',
          'file_extensions' => 'png gif jpg jpeg',
          'alt_field' => FALSE,
          'alt_field_required' => FALSE,
          'title_field' => FALSE,
          'title_field_required' => FALSE,
          'handler' => 'default:file',
        ],
      ],
    ];
  }

  /**
   * Return the fields for Product List type field.
   *
   * @return array
   *   Returns the list of fields to create.
   */
  protected function getProductListFields() {
    return [
      'pattern' => [
        'source' => 'value_data/product_id',
        'process' => [
          [
            'plugin' => 'migration_lookup',
            'migration' => 'bigcommerce_product',
            'source' => 'value_data/product_id',
          ],
        ],
        'label' => 'Products',
        'field_name' => 'field_product_attribute_products',
        'type' => 'entity_reference',
        'required' => TRUE,
        'cardinality' => -1,
        'storage_settings' => [
          'target_type' => 'commerce_product',
        ],
        'instance_settings' => [
          'handler' => 'default:commerce_product',
          'header_settings' => [
            'target_bundles' => [
              'default' => 'default',
            ],
            'sort' => [
              'field' => 'title',
              'direction' => 'ASC',
            ],
            'auto_create' => FALSE,
            'auto_create_bundle' => FALSE,
          ],
        ],
      ],
    ];
  }

  /**
   * Return the fields for Product List With Image type field.
   *
   * @return array
   *   Returns the list of fields to create.
   */
  protected function getProductListWithImagesFields() {
    return $this->getProductListFields();
  }

}
