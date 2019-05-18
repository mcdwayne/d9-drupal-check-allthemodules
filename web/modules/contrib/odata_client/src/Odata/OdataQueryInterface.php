<?php

namespace Drupal\odata_client\Odata;

/**
 * Interface OdataQueryInterface.
 */
interface OdataQueryInterface {

  /**
   * Connect to OData server.
   *
   * @param string $odata_server
   *   The OData server configuratin machine name.
   * @param string $collection_name
   *   The OData collection name.
   */
  public function connect(string $odata_server,
    string $collection_name = NULL);

}
