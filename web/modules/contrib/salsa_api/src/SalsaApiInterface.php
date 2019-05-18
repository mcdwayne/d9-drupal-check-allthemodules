<?php
/**
 * @file
 * Contains \Drupal\salsa_api\SalsaApiInterface.
 */

namespace Drupal\salsa_api;

/**
 * Declare the interface 'SalsaApiInterface'.
 */
interface SalsaApiInterface {
  /**
   * Login successful.
   */
  const CONNECTION_OK = 1;

  /**
   * Login failed, incorrect password and/or username.
   */
  const CONNECTION_AUTHENTICATION_FAILED = 2;

  /**
   * 404 page / server down / any other error.
   */
  const CONNECTION_WRONG_URL = 3;

  /**
   * Data format required when submitting data to Salsa.
   */
  const DATE_FORMAT = 'd.m.Y H:i';

  /**
   * The status for event signups.
   */
  const EVENT_STATUS_SIGNED_UP = 'Signed Up';

  /**
   * Calls connection/authentication and builds a HTTP request.
   *
   * @param string $path
   *   The name of the Salsa script to call, e.g. getObject.sjs, followed by a
   *   '?' and POST data to send to Salsa.
   *
   * @return mixed
   *   The returned data from the HTTP request.
   */
  public function getRequest($path);

  /**
   * Deletes a tag from a Salsa object.
   *
   * @param object $object
   *   Salsa entity object / table.
   *
   * @param string $key
   *   Salsa entity key.
   *
   * @param string $tag
   *   Tag to be deleted.
   *
   * @throws \Drupal\salsa_api\SalsaQueryException
   * @throws \Drupal\salsa_api\SalsaConnectionException
   */
  public function deleteTag($object, $key, $tag);

  /**
   * Uploads a file to Salsa.
   *
   * @param object $file
   *   Uploaded file object.
   * @param array $properties
   *   An array of properties (content disposition key/value pairs).
   *
   * @throws \Drupal\salsa_api\SalsaQueryException
   * @throws \Drupal\salsa_api\SalsaConnectionException
   */
  public function upload($file, $properties);

  /**
   * Performs a simple count of items in the Salsa database.
   *
   * The count is performed over items matching a given condition.
   *
   * @param object $object
   *   The Salsa object/table to query
   * @param array $conditions
   *   An array of conditions. The key is the column name, the value can be one
   *   of:
   *   - A value: used as is, with the = operator.
   *   - An array of values: Imploded with , and the IN operator is used.
   *   - A value with a %: The LIKE operator is used.
   *   - An array with the keys #operator and #value. Supporter operators are
   *     =, >=, <=, <|>, LIKE, IN, NOT IN, IS NOT EMPTY, IS EMPTY. EMPTY is
   *     equal to an NULL OR empty value.
   * @param string $column_count
   *   The column to count.
   *
   * @return int
   *   The count of items matching the given condition.
   *
   * @throws \Drupal\salsa_api\SalsaQueryException
   * @throws \Drupal\salsa_api\SalsaConnectionException
   */
  public function getCount($object, array $conditions = array(), $column_count = NULL);

  /**
   * Performs a simple count of items in the Salsa database.
   *
   * The count is performed over items matching a given condition.
   *
   * @param object $object
   *   The Salsa object/table to query.
   * @param array $group_by
   *   An array of columns to group by.
   * @param array $conditions
   *   An array of conditions. The key is the column name, the value can be one
   *   of:
   *   - A value: used as is, with the = operator.
   *   - An array of values: Imploded with , and the IN operator is used.
   *   - A value with a %: The LIKE operator is used.
   *   - An array with the keys #operator and #value. Supporter operators are
   *     =, >=, <=, <|>, LIKE, IN, NOT IN, IS NOT EMPTY, IS EMPTY. EMPTY is
   *     equal to an NULL OR empty value.
   * @param string $column_count
   *   The column to count.
   * @param array $order_by
   *   An array of columns to order by. Use -column to sort descending.
   * @param int|mixed $limit
   *   Limit the number of returned objects, Either an integer or "offset,limit"
   *   No more than 100 objects can be returned at once.
   *
   * @return array
   *   Array of various counts. If no groupBy column is specified, inside the
   *   returned array are four string elements:
   *   - count: The number of items matching the condition
   *   - sum: The sum of the items in columnCount in the result set
   *   - max: The largest value in the columnCount in the result set
   *   - min: The smallest value in the columnCount in the result set
   *   If one or more groupBy columns are specified, the returned array contains
   *   a numbered array for each group of the result set. Inside this array are
   *   five string elements:
   *   - [groupBy column name]: The value of the named groupBy column used to
   *     group the result set
   *   - count: The number of items in this group of the result set
   *   - sum: The sum of the items in the columnCount in this group of the
   *     result set
   *   - max: The largest value in the columnCount in this group of the result
   *     set
   *   - min: The smallest value in the columnCount in this group of the result
   *     set
   *
   * @throws \Drupal\salsa_api\SalsaQueryException
   * @throws \Drupal\salsa_api\SalsaConnectionException
   */
  public function getCounts($object, array $group_by = array(), array $conditions = array(), $column_count = NULL, array $order_by = array(), $limit = NULL);

  /**
   * Queries across multiple Salsa objects by performing a left join.
   *
   * @param string $objects
   *   String of the objects and how they should be joined, in the form of
   *   tableA(joinField)tableB, e.g. supporter(supporter_KEY)donation. See
   *   https://help.salsalabs.com/entries/23353157-Using-getLeftJoin-sjs-and-conditions
   *   for more information.
   * @param array $conditions
   *   Array of conditions. The key is the column name, the value can be one of:
   *   - A value: used as is, with the = operator.
   *   - An array of values: Imploded with , and the IN operator is used.
   *   - A value with a %: The LIKE operator is used.
   *   - An array with the keys #operator and #value. Supporter operators are
   *     =, >=, <=, <|>, LIKE, IN, NOT IN, IS NOT EMPTY, IS EMPTY. EMPTY is
   *     equal to an NULL OR empty value.
   * @param int|mixed $limit
   *   Limit the number of returned objects, Either an integer or "offset,limit"
   *   No more than 100 objects can be returned at once.
   * @param array $include
   *   An array of columns that should be included in the response. Everything
   *   is returned if empty.
   * @param array $order_by
   *   An array of columns to order by. Use -column to sort descending.
   * @param array $group_by
   *   An array of columns to group by.
   *
   * @return array
   *   Array of objects.
   *
   * @throws \Drupal\salsa_api\SalsaQueryException
   * @throws \Drupal\salsa_api\SalsaConnectionException
   */
  public function getLeftJoin($objects, array $conditions = array(), $limit = NULL, array $include = array(), array $order_by = array(), array $group_by = array());

  /**
   * Retrieves a single Salsa object.
   *
   * @param string $object
   *   Name of the salsa object/table, e.g. supporter.
   * @param string $key
   *   Key of the object to return, e.g. supporter_KEY.
   *
   * @return array
   *   Array containing the Salsa object requested.
   *
   * @throws \Drupal\salsa_api\SalsaQueryException
   * @throws \Drupal\salsa_api\SalsaConnectionException
   */
  public function getObject($object, $key);

  /**
   * Returns multiple Salsa objects matching a given condition.
   *
   * @param string $object
   *   Name of the salsa object/table, e.g. supporter.
   * @param array $conditions
   *   Array of conditions. The key is the column name, the value can be one of
   *   - A value: used as is, with the = operator.
   *   - An array of values: Imploded with , and the IN operator is used.
   *   - A value with a %: The LIKE operator is used.
   *   - An array with the keys #operator and #value. Supporter operators are
   *     =, >=, <=, <|>, LIKE, IN, NOT IN, IS NOT EMPTY, IS EMPTY. EMPTY is
   *     equal to an NULL OR empty value.
   * @param array $limit
   *   Limit the number of returned objects, Either an integer or "offset,limit"
   *   No more than 100 objects can be returned at once.
   * @param array $include
   *   An array of columns that should be included in the response. Everything
   *   is returned if empty.
   * @param array $order_by
   *   An array of columns to order by. Use -column to sort descending.
   * @param array $group_by
   *   An array of columns to group by.
   *
   * @return array[]
   *   Array of object value arrays.
   *
   * @throws \Drupal\salsa_api\SalsaQueryException
   * @throws \Drupal\salsa_api\SalsaConnectionException
   */
  public function getObjects($object, array $conditions = array(), $limit = NULL, array $include = array(), array $order_by = array(), array $group_by = array());

  /**
   * Retrieves a report from Salsa.
   *
   * @param string $key
   *   The report_KEY of the report to retrieve.
   *
   * @return array
   *   An array containing the report. This array contains a 'row' array, which
   *   contains an array for each row of the report. Inside the 'row' array is
   *   an associative array containing each column/field and value in that row.
   *
   * @throws \Drupal\salsa_api\SalsaQueryException
   * @throws \Drupal\salsa_api\SalsaConnectionException
   */
  public function getReport($key);

  /**
   * Retrieves the database schema of a Salsa object.
   *
   * @param string $table
   *   The name of the Salsa object for which to retrieve the schema.
   *
   * @return array
   *   An array containing the object's database schema. Each numbered array
   *   element describes one column of the database, and contains six arrays,
   *   each containing a string describing one attribute of that column. Any
   *   attribute without a value is set to NULL.
   *    - Field: The field name
   *    - Type: The SQL datatype for the field (e.g. int(16))
   *    - Null: Are null values allowed? (YES, NO)
   *    - Key: Is the field a MySQL key? (PRI, UNI, MUL)
   *    - Default: The default value to use if none is supplied
   *    - Extra: Any extra attributes for the column (e.g. auto_increment)
   *
   * @throws \Drupal\salsa_api\SalsaQueryException
   * @throws \Drupal\salsa_api\SalsaConnectionException
   */
  public function describe($table);

  /**
   * Retrieves the database schema of a Salsa object.
   *
   * The schema is retrieved in a different format than used by describe().
   *
   * @param string $table
   *   The name of the Salsa object for which to retrieve the schema.
   *
   * @return array
   *   An array containing the object's database schema. Each numbered array
   *   element describes one column of the database, and contains five arrays,
   *   each containing a string describing one attribute of that column. Any
   *   attribute without a value is set to NULL.
   *    - name: The machine-readable name of the field
   *    - nullable: Are null values allowed (0 or 1)
   *    - type: The SQL datatype of the field (e.g., int, varchar)
   *    - defaultValue: The default value to use if none is supplied
   *    - label: The human-readable name of the field
   *
   * @throws \Drupal\salsa_api\SalsaQueryException
   * @throws \Drupal\salsa_api\SalsaConnectionException
   */
  public function describe2($table);

  /**
   * Save Salsa objects.
   *
   * @param string $object
   *   Name of the Salsa object/table to save to, e.g. supporter.
   * @param array $fields
   *   An associative array of fields (keys) and values to save.
   * @param array $links
   *   An array containing associative arrays of link tables and linkKeys. Used
   *   when saving to more than one table at a time.
   *
   * @return int
   *   The key of the updated or newly created object.
   *
   * @throws \Drupal\salsa_api\SalsaQueryException
   * @throws \Drupal\salsa_api\SalsaConnectionException
   */
  public function save($object, array $fields = array(), array $links = array());

  /**
   * Tests connection and authentication to Salsa API.
   *
   * @param string $url
   *   The URL value that is received from the form.
   *
   * @param string $username
   *   The username value that is received from the form.
   *
   * @param string $password
   *   The password value that is received from the form.
   *
   * @return Int
   *   Validation flag (constant) from request.
   */
  public function testConnect($url, $username, $password);

}
