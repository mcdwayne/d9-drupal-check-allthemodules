<?php

namespace Drupal\odata_client\Odata;

/**
 * Interface InputOutputServiceInterface.
 */
interface InputOutputServiceInterface {

  /**
   * Connect to OData server from given configuration.
   *
   * @param string $config_name
   *   The configuration entity name.
   *
   * @return \Drupal\odata_client\Odata\InputOutputServiceInterface
   *   This class.
   */
  public function connect(string $config_name): InputOutputServiceInterface;

  /**
   * Set the OData type on OData server.
   *
   * @param string $type
   *   The OData type.
   */
  public function setOdataType(string $type);

  /**
   * Get the OData type on OData server.
   */
  public function getOdataType();

  /**
   * Set the default collection name on OData server.
   *
   * @param string $collection_name
   *   The collection name.
   */
  public function setDefaultCollectionName(string $collection_name);

  /**
   * Get the default collection name on OData server.
   */
  public function getDefaultCollectionName();

  /**
   * Get the object of value from OData server from given collection.
   *
   * @param array $chain
   *   The chain options array:
   *     An associate array:
   *       select: array
   *         - columns name
   *       where: array
   *         - elements contain array:
   *           column name,
   *           operator,
   *           value
   *       order: array
   *         - elements contain array:
   *           column name,
   *           order direction (asc or desc)
   *       skip: int
   *         - number of skiping elements
   *       take: int
   *         - number of returning elements.
   *
   * @code
   * Example:
   *   $chains = [
   *     select => ['Name', 'Gender'],
   *     where => [
   *       ['Gender', '=', 'Female'],
   *     ],
   *     order => [
   *       ['Name', 'asc'],
   *     ],
   *     skip => 0,
   *     take => 10,
   *   ];
   * @endcode
   *
   * @return \Illuminate\Support\Collection
   *   The result collection.
   */
  public function get(array $chain = []);

  /**
   * The find function.
   *
   * @param string $id
   *   The entity id or key.
   *
   * @return \Illuminate\Support\Collection
   *   The result collection.
   */
  public function find(string $id);

  /**
   * The count function.
   *
   * @return int
   *   Entity counting on collection.
   */
  public function count();

  /**
   * Prepare authentication to OData server from given configuration.
   *
   * @param array $data
   *   The data to post to OData server.
   */
  public function post(array $data);

}
