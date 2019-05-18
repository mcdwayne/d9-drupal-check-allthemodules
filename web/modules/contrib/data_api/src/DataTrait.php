<?php

namespace Drupal\data_api;

use AKlump\Data\DataInterface;

/**
 * Class DataApiTrait.
 *
 * Add this trait to classes that need to use Drupal\data_api\Data internally.
 * The using class SHOULD inject a Data object in the constructor as follows:
 *
 * @code
 *   public function __construct(Data $dataApiData)
 *   {
 *     $this->setDataApiData($dataApiData);
 *   }
 * @endcode
 *
 * Notice the reserved properties: g, e, n, u, when you need to use the object
 * in a method, choose the appropriate property by node type and do...
 *
 * @code
 *   ...
 *   $body = $this->n->get($node, 'field_body.0.value');
 *   ...
 * @endcode
 *
 * For static methods you MUST use the data_api() function instead.
 *
 * @code
 *   $body = data_api('node')->get($node, 'field_body.0.value');
 * @endcode
 */
trait DataTrait {

  protected $dataApiData;

  /**
   * A Data object with no entity type (global).
   *
   * @var \AKlump\Data\DataInterface
   */
  protected $g;

  /**
   * An instance with the entity type set by the caller.
   *
   * Reserved for an entity Data object.  Caller must instantiate with the
   * correct entity type per situation and logical context.  So if the caller
   * is a class that is called NodeTransformer then the logical entity type
   * would be node.
   *
   * @var \AKlump\Data\DataInterface
   *
   * The constructor would need to do something like this:
   * @code
   *      $this->e = $this->getDataApiData('commerce_product');
   * @endcode
   */
  protected $e;

  /**
   * A node Data object.
   *
   * @var \AKlump\Data\DataInterface
   */
  protected $n;

  /**
   * A user Data object.
   *
   * @var \AKlump\Data\DataInterface
   */
  protected $u;

  /**
   * Return a new instance of a Data object.
   *
   * @param string $entity_type
   *   Optional.  The entity type if applicable.
   *
   * @return \AKlump\Data\DataInterface
   *   And instance of \AKlump\Data\DataInterface.
   */
  protected function getDataApiData($entity_type = NULL) {
    if (is_null($entity_type)) {
      return $this->g;
    }

    static $objects = [];
    if (!array_key_exists($entity_type, $objects)) {
      $data = clone $this->g;
      $objects[$entity_type] = $data->setEntityType($entity_type);
    }

    return $objects[$entity_type];
  }

  /**
   * Set the DataApi objects.
   *
   * @param \AKlump\Data\DataInterface $data
   *   An instance of \AKlump\Data\DataInterface.
   *
   * @return $this
   */
  public function setDataApiData(DataInterface $data) {
    $this->dataApiData = $data;
    $this->g = clone $this->dataApiData;
    $this->g->setEntityType(NULL);
    $this->n = $this->getDataApiData('node');
    $this->u = $this->getDataApiData('user');

    return $this;
  }
}
