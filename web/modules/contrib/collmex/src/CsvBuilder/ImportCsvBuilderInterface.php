<?php

namespace Drupal\collmex\CsvBuilder;

interface ImportCsvBuilderInterface extends BaseCsvBuilderInterface {

  /**
   * Build import.
   *
   * @param array $values
   *   Values.
   *
   * @return string
   *   The CSV.
   */
  public function buildImport(array $values);

  /**
   * Build import.
   *
   * @param array $destinationIdValues
   *   Destination ID values.
   *
   * @return string
   *   The CSV.
   */
  public function buildRollback(array $destinationIdValues);

  /**
   * Get delete mark values, if the object should be marked deleted.
   *
   * @return array
   */
  public function getDeleteMarkValues();

}
