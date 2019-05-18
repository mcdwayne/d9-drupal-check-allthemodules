<?php

namespace Drupal\odata_client\Odata;

/**
 * OdataQueryBase class implements the EntityQuery format query API.
 */
class OdataQueryBase implements OdataQueryBaseInterface {

  /**
   * The \Drupal\odata_client\InputOutputServiceInterface definition.
   *
   * @var Drupal\odata_client\InputOutputServiceInterface
   *   The OData IO service interface.
   */
  protected $odataServer;

  /**
   * The conditions definition.
   *
   * @var array
   *   The parameters array.
   */
  protected $chain = [];

  /**
   * Constuct a new OdataQueryBase object.
   *
   * @param string $odata_server
   *   The OData server configuration name.
   * @param string $collection_name
   *   The OData collection name to execute query (optional).
   */
  public function __construct(string $odata_server,
    string $collection_name = NULL) {
    $this->odataServer = \Drupal::service('odata_client.io');
    $this->odataServer->connect($odata_server);
    if (!empty($collection_name)) {
      $this->odataServer->setDefaultCollectionName($collection_name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function fields(array $fields) {
    $this->chain['select'][] = $fields;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function condition(string $field,
    string $value,
    string $operator = '=') {
    $this->chain['where'][] = [$field, $operator, $value];
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function range(int $start = 0,
    int $length = 0) {
    $this->chain['skip'] = $start;
    $this->chain['take'] = $length;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function orderBy(string $field,
    string $direction = 'ASC') {
    $this->chain['order'][] = [$field, $direction];
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    return $this->odataServer->get($this->chain);
  }

}
