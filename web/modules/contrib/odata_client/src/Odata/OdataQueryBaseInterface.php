<?php

namespace Drupal\odata_client\Odata;

/**
 * Interface for OData queries.
 */
interface OdataQueryBaseInterface {

  /**
   * Add OData collection fields parameters.
   *
   * @param array $fields
   *   The fields which includes result.
   *
   * @return \Drupal\odata_client\Odata\OdataQueryInterface
   *   The called object.
   */
  public function fields(array $fields);

  /**
   * Add OData collection where filter parameters.
   *
   * @param string $field
   *   The field that contain the value.
   * @param string $value
   *   The value what contain the field.
   * @param string $operator
   *   The operator for condition between field and value.
   *     Alloved operators:
   *       '=', '<', '>', '<=', '>=', '<>', '!=',
   *       'like', 'like binary', 'not like', 'between', 'ilike',
   *       '&', '|', '^', '<<', '>>',
   *       'rlike', 'regexp', 'not regexp',
   *       '~', '~*', '!~', '!~*', 'similar to',
   *       'not similar to', 'not ilike', '~~*', '!~~*',
   *       'contains', 'startswith', 'endswith'.
   *
   * @return \Drupal\odata_client\Odata\OdataQueryInterface
   *   The called object.
   */
  public function condition(string $field,
    string $value,
    string $operator = '=');

  /**
   * Add OData collection skip and take parameters.
   *
   * @param int $start
   *   The query start from number of element.
   * @param int $length
   *   The query contains length elements.
   *
   * @return \Drupal\odata_client\Odata\OdataQueryInterface
   *   The called object.
   */
  public function range(int $start = 0,
    int $length = 0);

  /**
   * Add OData collection order parameters.
   *
   * @param string $field
   *   Name of a field.
   * @param string $direction
   *   The direction of order.
   *
   * @return \Drupal\odata_client\Odata\OdataQueryInterface
   *   The called object.
   */
  public function orderBy(string $field,
    string $direction = 'ASC');

  /**
   * Execute the query.
   *
   * @return \Illuminate\Support\Collection
   *   The result collection. Or NULL if throw error.
   */
  public function execute();

}
