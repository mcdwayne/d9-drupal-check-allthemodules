<?php

namespace Drupal\bigcommerce\Plugin\migrate\source;

use Drupal\bigcommerce\Exception\UnconfiguredException;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\migrate\Plugin\MigrationInterface;
use BigCommerce\Api\v3\Api\CatalogApi;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Plugin\RequirementsInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Abstract class to connect to the BigCommerce API.
 */
abstract class BigCommerceSource extends SourcePluginBase implements ContainerFactoryPluginInterface, RequirementsInterface {

  /**
   * BigCommerce Catalog API instance.
   *
   * @var \BigCommerce\Api\v3\Api\CatalogApi
   */
  protected $catalogApi;

  /**
   * Information on the source fields to be extracted from the data.
   *
   * @var array[]
   *   Array of field information keyed by field names. A 'label' subkey
   *   describes the field for migration tools; a 'path' subkey provides the
   *   source-specific path for obtaining the value.
   */
  protected $fields = [];

  /**
   * Description of the unique ID fields for this source.
   *
   * @var array[]
   *   Each array member is keyed by a field name, with a value that is an
   *   array with a single member with key 'type' and value a column type such
   *   as 'integer'.
   */
  protected $ids = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, CatalogApi $catalogApi = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->catalogApi = $catalogApi;
    $this->fields = $configuration['fields'] ?? [];
    $this->ids = $configuration['ids'] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    try {
      $catalog_api = $container->get('bigcommerce.catalog');
    }
    catch (UnconfiguredException $e) {
      $catalog_api = NULL;
    }
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $catalog_api
    );
  }

  /**
   * {@inheritdoc}
   */
  public function checkRequirements() {
    if ($this->catalogApi === NULL) {
      throw new UnconfiguredException('In order to import products from the BigCommerce the API connection must be configured.');
    }
  }

  /**
   * Return a string representing the plugin name.
   *
   * @return string
   *   The plugin name.
   */
  public function __toString() {
    return 'BigCommerce Import - ' . $this->configuration['plugin'];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [];
    foreach ($this->fields as $field_info) {
      $fields[$field_info['name']] = isset($field_info['label']) ? $field_info['label'] : $field_info['name'];
    }
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return $this->ids;
  }

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    $params = [
      'limit' => 50,
      'page' => 0,
    ];
    return $this->getYield($params);
  }

  /**
   * Use this method to call the API and fetch response.
   *
   * @param array $params
   *   The array of parameters to be used when calling the API.
   */
  public function getSourceResponse(array $params) {
  }

  /**
   * Prepare one row of the fetched data.
   *
   * @param array $params
   *   The array of parameters to be used when calling the API.
   *
   * @codingStandardsIgnoreStart
   *
   * @return \Generator
   *   A new row, one for each unique row.
   *
   * @codingStandardsIgnoreEnd
   */
  public function getYield(array $params) {
    $total_pages = 1;
    while ($params['page'] < $total_pages) {
      $params['page']++;

      $response = $this->getSourceResponse($params);
      foreach ($response->getData() as $row) {
        yield $row->get();
      }

      if ($params['page'] === 1) {
        $total_pages = $response->getMeta()->getPagination()->getTotalPages();
      }
    }
  }

  /**
   * Gets whether this source tracks changes.
   *
   * BigCommerce content entity migrations should have trackChanges set to TRUE
   * so the Drupal entities are updated when they are in the BigCommerce API.
   *
   * @return bool
   */
  public function trackChanges() {
    return $this->trackChanges;
  }

}
