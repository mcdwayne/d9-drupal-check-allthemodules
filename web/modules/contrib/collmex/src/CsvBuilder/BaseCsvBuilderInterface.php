<?php


namespace Drupal\collmex\CsvBuilder;


interface BaseCsvBuilderInterface {

  const TYPE_CHAR = 'c';
  const TYPE_INT = 'i';
  const TYPE_NUM = 'n';
  const TYPE_DATE = 'd';
  const TYPE_MONEY = 'm';
  const TYPE_TIME = 't';

  /**
   * Get fields.
   *
   * @return string[]
   */
  public function getFields();

  /**
   * Get ID keys.
   *
   * @return string[]
   */
  public function getIdKeys();

  /**
   * Get default values.
   *
   * @return array
   */
  public function getDefaultValues();

  /**
   * Get field type.
   *
   * @param string $key
   *
   * @return string
   */
  public function getFieldType($key);

  /**
   * Get field length.
   *
   * @param string $key
   *
   * @return string
   */
  public function getFieldLength($key);

}
