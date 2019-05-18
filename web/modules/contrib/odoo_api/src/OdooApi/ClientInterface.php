<?php

namespace Drupal\odoo_api\OdooApi;

/**
 * Interface OdooApiClientInterface.
 */
interface ClientInterface {

  /**
   * The Odoo date format.
   */
  const ODOO_DATE_FORMAT = 'Y-m-d';

  /**
   * The Odoo datetime format.
   */
  const ODOO_DATETIME_FORMAT = 'Y-m-d H:i:s';

  /**
   * Gets Odoo instance version info.
   *
   * Typically used to check the connection.
   *
   * @return array
   *   Odoo version info (as seen at API doc).
   *
   * @see https://www.odoo.com/documentation/11.0/webservices/odoo.html#logging-in
   */
  public function getVersionInfo();

  /**
   * Logs in.
   *
   * @return bool
   *   Whether log in was successful.
   */
  public function authenticate();

  /**
   * Performs a search query.
   *
   * @param string $model_name
   *   Odoo model name.
   * @param array $filter
   *   Odoo domain filter.
   * @param int|null $offset
   *   Pagination offset.
   * @param int|null $limit
   *   Limit number of records returned.
   * @param string|null $order
   *   Search order. May be either 'field direction' string or NULL for default.
   *   Examples: 'id asc', 'id desc', 'write_at desc'.
   *
   *   NOTE: This parameter is not documented at Odoo web services API doc so it
   *   could break at any time.
   *
   * @return int[]
   *   Array of database identifiers.
   *
   * @see https://www.odoo.com/documentation/11.0/webservices/odoo.html#list-records
   * @see https://www.odoo.com/documentation/11.0/reference/orm.html#odoo.models.Model.search
   * @see https://www.odoo.com/documentation/11.0/reference/orm.html#reference-orm-domains
   */
  public function search($model_name, array $filter = [], $offset = NULL, $limit = NULL, $order = NULL);

  /**
   * Performs a count query.
   *
   * @param string $model_name
   *   Odoo model name.
   * @param array $filter
   *   Odoo domain filter.
   *
   * @return int
   *   Number of records.
   *
   * @see https://www.odoo.com/documentation/11.0/webservices/odoo.html#list-records
   * @see https://www.odoo.com/documentation/11.0/reference/orm.html#odoo.models.Model.search
   * @see https://www.odoo.com/documentation/11.0/reference/orm.html#reference-orm-domains
   */
  public function count($model_name, array $filter = []);

  /**
   * Performs a read query.
   *
   * @param string $model_name
   *   Odoo model name.
   * @param array $ids
   *   Array of Odoo database identifiers, as returned by search().
   * @param array|null $fields
   *   Array of fields to fetch. NULL means all fields.
   *
   * @return array
   *   Array of Odoo objects.
   *
   * @see \Drupal\odoo_api\OdooApi\ClientInterface::search()
   * @see https://www.odoo.com/documentation/11.0/webservices/odoo.html#read-records
   * @see https://www.odoo.com/documentation/11.0/reference/orm.html#odoo.models.Model.read
   */
  public function read($model_name, array $ids, $fields = NULL);

  /**
   * Performs a fields_get query.
   *
   * @param string $model_name
   *   Odoo model name.
   * @param array $fields
   *   Array of fields to fetch. Empty array means all fields.
   * @param array|null $attributes
   *   Array of fields metadata attributes to fetch. NULL means 'type', 'string'
   *   and 'help', as suggested by Odoo API doc.
   *
   * @return array
   *   Array of Odoo model fields.
   *
   * @see https://www.odoo.com/documentation/11.0/webservices/odoo.html#listing-record-fields
   * @see https://www.odoo.com/documentation/11.0/reference/orm.html#odoo.models.Model.fields_get
   */
  public function fieldsGet($model_name, array $fields = [], $attributes = NULL);

  /**
   * Performs a search_read query.
   *
   * This is a handy shortcut for search() + read().
   *
   * @param string $model_name
   *   Odoo model name.
   * @param array $filter
   *   Odoo domain filter.
   * @param array|null $fields
   *   Array of fields to fetch. NULL means all fields.
   * @param int|null $offset
   *   Pagination offset.
   * @param int|null $limit
   *   Limit number of records returned.
   * @param string|null $order
   *   Search order. May be either 'field direction' string or NULL for default.
   *   Examples: 'id asc', 'id desc', 'write_at desc'.
   *
   *   NOTE: This parameter is not documented at Odoo web services API doc so it
   *   could break at any time.
   *
   * @return array
   *   Array of Odoo objects.
   *
   * @see https://www.odoo.com/documentation/11.0/webservices/odoo.html#search-and-read
   * @see \Drupal\odoo_api\OdooApi\ClientInterface::search()
   * @see \Drupal\odoo_api\OdooApi\ClientInterface::read()
   */
  public function searchRead($model_name, array $filter = [], $fields = NULL, $offset = NULL, $limit = NULL, $order = NULL);

  /**
   * An iterator wrapper around searchRead().
   *
   * This method may be used to iterate over Odoo objects easily.
   *
   * @param string $model_name
   *   Odoo model name.
   * @param array $filter
   *   Odoo domain filter.
   * @param array|null $fields
   *   Array of fields to fetch. NULL means all fields.
   * @param int|null $page_size
   *   Page size limit.
   * @param string|null $order
   *   Search order. May be either 'field direction' string or NULL for default.
   *   Examples: 'id asc', 'id desc', 'write_at desc'.
   *
   *   NOTE: This parameter is not documented at Odoo web services API doc so it
   *   could break at any time.
   *
   * @return \Generator
   *   Generator of models.
   */
  public function searchReadIterate($model_name, array $filter = [], $fields = NULL, $page_size = 50, $order = NULL);

  /**
   * Performs a create query.
   *
   * @param string $model_name
   *   Odoo model name.
   * @param array|null $fields
   *   Array of fields to create a new object.
   *
   * @return int
   *   ID of new database record.
   *
   * @see https://www.odoo.com/documentation/11.0/webservices/odoo.html#create-records
   * @see https://www.odoo.com/documentation/11.0/reference/orm.html#odoo.models.Model.create
   */
  public function create($model_name, array $fields = []);

  /**
   * Performs a write query.
   *
   * @param string $model_name
   *   Odoo model name.
   * @param int[] $ids
   *   Database IDs of objects to update.
   *
   *   Note: passing string IDs (like '14') may cause exceptions. Be sure to
   *   cast IDs to integers.
   * @param array|null $fields
   *   Array of fields to update in objects.
   *
   * @return bool
   *   Whether write query was successful.
   *
   * @see \Drupal\odoo_api\OdooApi\ClientInterface::search()
   * @see https://www.odoo.com/documentation/11.0/webservices/odoo.html#update-records
   * @see https://www.odoo.com/documentation/11.0/reference/orm.html#odoo.models.Model.write
   */
  public function write($model_name, array $ids, array $fields);

  /**
   * Performs an unlink query.
   *
   * This method deletes records from database.
   *
   * @param string $model_name
   *   Odoo model name.
   * @param int[] $ids
   *   Database IDs of objects to delete.
   *
   * @return bool
   *   Whether delete query was successful.
   *
   * @see \Drupal\odoo_api\OdooApi\ClientInterface::search()
   * @see https://www.odoo.com/documentation/11.0/webservices/odoo.html#delete-records
   * @see https://www.odoo.com/documentation/11.0/reference/orm.html#odoo.models.Model.unlink
   */
  public function unlink($model_name, array $ids);

  /**
   * Calls a specific method of the model.
   *
   * @param string $model_name
   *   Odoo model name, like 'sale.order'.
   * @param string $method
   *   Odoo model method name. E.g. 'action_invoice_create'.
   * @param array $arguments
   *   Odoo model method arguments.
   * @param array $named_arguments
   *   Odoo model method named arguments. If supplied, they will be mapped to
   *   Python function named arguments on Odoo side.
   *
   * @return mixed
   *   Odoo API returned value.
   *
   * @throws \Drupal\odoo_api\OdooApi\Exception\AuthException
   *
   * @see https://www.odoo.com/documentation/11.0/webservices/odoo.html#calling-methods
   * @see https://www.odoo.com/documentation/11.0/reference/orm.html#common-orm-methods
   */
  public function rawModelApiCall($model_name, $method, array $arguments = [], array $named_arguments = []);

}
