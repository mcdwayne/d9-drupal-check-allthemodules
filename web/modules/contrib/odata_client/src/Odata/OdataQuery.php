<?php

namespace Drupal\odata_client\Odata;

use Drupal\Core\DependencyInjection\Container;

/**
 * Class OdataQuery.
 */
class OdataQuery extends OdataQueryBase implements OdataQueryInterface {

  /**
   * Drupal\Core\DependencyInjection\ContainerBuilder definition.
   *
   * @var \Drupal\Core\DependencyInjection\ContainerBuilder
   */
  protected $serviceContainer;

  /**
   * Constructs a new OdataQuery object.
   */
  public function __construct(Container $service_container) {
    $this->serviceContainer = $service_container;
  }

  /**
   * {@inheritdoc}
   */
  public function connect(string $odata_server,
    string $collection_name = NULL) {
    $this->odataServer = $this->serviceContainer->get('odata_client.io');
    $this->odataServer->connect($odata_server);
    if (!empty($collection_name)) {
      $this->odataServer->setDefaultCollectionName($collection_name);
    }

    return $this;
  }

}
