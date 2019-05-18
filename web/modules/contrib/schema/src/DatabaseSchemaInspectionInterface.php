<?php
/**
 * @file
 * Contains Drupal\schema\DatabaseSchemaInspectionInterface.
 */
namespace Drupal\schema;

interface DatabaseSchemaInspectionInterface extends DatabaseSchemaInterface {
  public function prepareTableComment($comment, $pdo_quote = TRUE);
  public function prepareColumnComment($comment, $pdo_quote = TRUE);
  public function updateTableComment($table_name, $comment);
  public function inspect($connection = NULL, $table_name = NULL);
  public function recreatePrimaryKey($table_name, $primary_key);
  public function getIndexes($table_name);
}
