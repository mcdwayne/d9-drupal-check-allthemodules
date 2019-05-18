<?php

namespace Drupal\odoo_api\OdooApi\Data;

/**
 * Model fields service interface.
 */
interface ModelFieldsResolverInterface {

  /**
   * Gets list of model fields.
   *
   * @param string $model_name
   *   The model name.
   *
   * @return array
   *   List of fields.
   */
  public function getModelFieldsData($model_name);

  /**
   * Returns a type of the field.
   *
   * @param string $field_name
   *   The field name.
   * @param string $model_name
   *   The model name.
   *
   * @return bool|string
   *   The field type or FALSE if it doesn't exist in the specified model.
   */
  public function getFieldType($field_name, $model_name);

}
