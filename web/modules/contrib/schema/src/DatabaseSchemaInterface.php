<?php
/**
 * @file
 * Contains Drupal\schema\DatabaseSchemaInterface.
 */

namespace Drupal\schema;

/**
 * This interface describes public methods of \Drupal\Core\Database\Schema.
 */
interface DatabaseSchemaInterface {

  /**
   * Check if a table exists.
   *
   * @param $table
   *   The name of the table in drupal (no prefixing).
   *
   * @return
   *   TRUE if the given table exists, otherwise FALSE.
   */
  public function tableExists($table);

  /**
   * Find all tables that are like the specified base table name.
   *
   * @param $table_expression
   *   An SQL expression, for example "simpletest%" (without the quotes).
   *   BEWARE: this is not prefixed, the caller should take care of that.
   *
   * @return
   *   Array, both the keys and the values are the matching tables.
   */
  public function findTables($table_expression);

  /**
   * Check if a column exists in the given table.
   *
   * @param $table
   *   The name of the table in drupal (no prefixing).
   * @param $name
   *   The name of the column.
   *
   * @return
   *   TRUE if the given column exists, otherwise FALSE.
   */
  public function fieldExists($table, $column);

  /**
   * Returns a mapping of Drupal schema field names to DB-native field types.
   *
   * Because different field types do not map 1:1 between databases, Drupal has
   * its own normalized field type names. This function returns a driver-specific
   * mapping table from Drupal names to the native names for each database.
   *
   * @return array
   *   An array of Schema API field types to driver-specific field types.
   */
  public function getFieldTypeMap();

  /**
   * Rename a table.
   *
   * @param $table
   *   The table to be renamed.
   * @param $new_name
   *   The new name for the table.
   *
   * @throws \Drupal\Core\Database\SchemaObjectDoesNotExistException
   *   If the specified table doesn't exist.
   * @throws \Drupal\Core\Database\SchemaObjectExistsException
   *   If a table with the specified new name already exists.
   */
  public function renameTable($table, $new_name);

  /**
   * Drop a table.
   *
   * @param $table
   *   The table to be dropped.
   *
   * @return
   *   TRUE if the table was successfully dropped, FALSE if there was no table
   *   by that name to begin with.
   */
  public function dropTable($table);

  /**
   * Add a new field to a table.
   *
   * @param $table
   *   Name of the table to be altered.
   * @param $field
   *   Name of the field to be added.
   * @param $spec
   *   The field specification array, as taken from a schema definition.
   *   The specification may also contain the key 'initial', the newly
   *   created field will be set to the value of the key in all rows.
   *   This is most useful for creating NOT NULL columns with no default
   *   value in existing tables.
   * @param $keys_new
   *   (optional) Keys and indexes specification to be created on the
   *   table along with adding the field. The format is the same as a
   *   table specification but without the 'fields' element. If you are
   *   adding a type 'serial' field, you MUST specify at least one key
   *   or index including it in this array. See db_change_field() for more
   *   explanation why.
   *
   * @throws \Drupal\Core\Database\SchemaObjectDoesNotExistException
   *   If the specified table doesn't exist.
   * @throws \Drupal\Core\Database\SchemaObjectExistsException
   *   If the specified table already has a field by that name.
   */
  public function addField($table, $field, $spec, $keys_new = array());

  /**
   * Drop a field.
   *
   * @param $table
   *   The table to be altered.
   * @param $field
   *   The field to be dropped.
   *
   * @return
   *   TRUE if the field was successfully dropped, FALSE if there was no field
   *   by that name to begin with.
   */
  public function dropField($table, $field);

  /**
   * Set the default value for a field.
   *
   * @param $table
   *   The table to be altered.
   * @param $field
   *   The field to be altered.
   * @param $default
   *   Default value to be set. NULL for 'default NULL'.
   *
   * @throws \Drupal\Core\Database\SchemaObjectDoesNotExistException
   *   If the specified table or field doesn't exist.
   */
  public function fieldSetDefault($table, $field, $default);

  /**
   * Set a field to have no default value.
   *
   * @param $table
   *   The table to be altered.
   * @param $field
   *   The field to be altered.
   *
   * @throws \Drupal\Core\Database\SchemaObjectDoesNotExistException
   *   If the specified table or field doesn't exist.
   */
  public function fieldSetNoDefault($table, $field);

  /**
   * Checks if an index exists in the given table.
   *
   * @param $table
   *   The name of the table in drupal (no prefixing).
   * @param $name
   *   The name of the index in drupal (no prefixing).
   *
   * @return
   *   TRUE if the given index exists, otherwise FALSE.
   */
  public function indexExists($table, $name);

  /**
   * Add a primary key.
   *
   * @param $table
   *   The table to be altered.
   * @param $fields
   *   Fields for the primary key.
   *
   * @throws \Drupal\Core\Database\SchemaObjectDoesNotExistException
   *   If the specified table doesn't exist.
   * @throws \Drupal\Core\Database\SchemaObjectExistsException
   *   If the specified table already has a primary key.
   */
  public function addPrimaryKey($table, $fields);

  /**
   * Drop the primary key.
   *
   * @param $table
   *   The table to be altered.
   *
   * @return
   *   TRUE if the primary key was successfully dropped, FALSE if there was no
   *   primary key on this table to begin with.
   */
  public function dropPrimaryKey($table);

  /**
   * Add a unique key.
   *
   * @param $table
   *   The table to be altered.
   * @param $name
   *   The name of the key.
   * @param $fields
   *   An array of field names.
   *
   * @throws \Drupal\Core\Database\SchemaObjectDoesNotExistException
   *   If the specified table doesn't exist.
   * @throws \Drupal\Core\Database\SchemaObjectExistsException
   *   If the specified table already has a key by that name.
   */
  public function addUniqueKey($table, $name, $fields);

  /**
   * Drop a unique key.
   *
   * @param $table
   *   The table to be altered.
   * @param $name
   *   The name of the key.
   *
   * @return
   *   TRUE if the key was successfully dropped, FALSE if there was no key by
   *   that name to begin with.
   */
  public function dropUniqueKey($table, $name);

  /**
   * Add an index.
   *
   * @param $table
   *   The table to be altered.
   * @param $name
   *   The name of the index.
   * @param $fields
   *   An array of field names.
   *
   * @throws \Drupal\Core\Database\SchemaObjectDoesNotExistException
   *   If the specified table doesn't exist.
   * @throws \Drupal\Core\Database\SchemaObjectExistsException
   *   If the specified table already has an index by that name.
   */
  public function addIndex($table, $name, $fields, array $spec);

  /**
   * Drop an index.
   *
   * @param $table
   *   The table to be altered.
   * @param $name
   *   The name of the index.
   *
   * @return
   *   TRUE if the index was successfully dropped, FALSE if there was no index
   *   by that name to begin with.
   */
  public function dropIndex($table, $name);

  /**
   * Change a field definition.
   *
   * IMPORTANT NOTE: To maintain database portability, you have to explicitly
   * recreate all indices and primary keys that are using the changed field.
   *
   * That means that you have to drop all affected keys and indexes with
   * db_drop_{primary_key,unique_key,index}() before calling db_change_field().
   * To recreate the keys and indices, pass the key definitions as the
   * optional $keys_new argument directly to db_change_field().
   *
   * For example, suppose you have:
   * @code
   * $schema['foo'] = array(
   *   'fields' => array(
   *     'bar' => array('type' => 'int', 'not null' => TRUE)
   *   ),
   *   'primary key' => array('bar')
   * );
   * @endcode
   * and you want to change foo.bar to be type serial, leaving it as the
   * primary key. The correct sequence is:
   * @code
   * db_drop_primary_key('foo');
   * db_change_field('foo', 'bar', 'bar',
   *   array('type' => 'serial', 'not null' => TRUE),
   *   array('primary key' => array('bar')));
   * @endcode
   *
   * The reasons for this are due to the different database engines:
   *
   * On PostgreSQL, changing a field definition involves adding a new field
   * and dropping an old one which* causes any indices, primary keys and
   * sequences (from serial-type fields) that use the changed field to be dropped.
   *
   * On MySQL, all type 'serial' fields must be part of at least one key
   * or index as soon as they are created. You cannot use
   * db_add_{primary_key,unique_key,index}() for this purpose because
   * the ALTER TABLE command will fail to add the column without a key
   * or index specification. The solution is to use the optional
   * $keys_new argument to create the key or index at the same time as
   * field.
   *
   * You could use db_add_{primary_key,unique_key,index}() in all cases
   * unless you are converting a field to be type serial. You can use
   * the $keys_new argument in all cases.
   *
   * @param $table
   *   Name of the table.
   * @param $field
   *   Name of the field to change.
   * @param $field_new
   *   New name for the field (set to the same as $field if you don't want to change the name).
   * @param $spec
   *   The field specification for the new field.
   * @param $keys_new
   *   (optional) Keys and indexes specification to be created on the
   *   table along with changing the field. The format is the same as a
   *   table specification but without the 'fields' element.
   *
   * @throws \Drupal\Core\Database\SchemaObjectDoesNotExistException
   *   If the specified table or source field doesn't exist.
   * @throws \Drupal\Core\Database\SchemaObjectExistsException
   *   If the specified destination field already exists.
   */
  public function changeField($table, $field, $field_new, $spec, $keys_new = array());

  /**
   * Create a new table from a Drupal table definition.
   *
   * @param $name
   *   The name of the table to create.
   * @param $table
   *   A Schema API table definition array.
   *
   * @throws \Drupal\Core\Database\SchemaObjectExistsException
   *   If the specified table already exists.
   */
  public function createTable($name, $table);

  /**
   * Return an array of field names from an array of key/index column specifiers.
   *
   * This is usually an identity function but if a key/index uses a column prefix
   * specification, this function extracts just the name.
   *
   * @param $fields
   *   An array of key/index column specifiers.
   *
   * @return
   *   An array of field names.
   */
  public function fieldNames($fields);

  /**
   * Prepare a table or column comment for database query.
   *
   * @param $comment
   *   The comment string to prepare.
   * @param $length
   *   Optional upper limit on the returned string length.
   *
   * @return
   *   The prepared comment.
   */
  public function prepareComment($comment, $length = NULL);
}
