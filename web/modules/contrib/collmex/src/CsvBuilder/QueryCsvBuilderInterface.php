<?php

namespace Drupal\collmex\CsvBuilder;

interface QueryCsvBuilderInterface extends BaseCsvBuilderInterface {

  /**
   * Build query.
   *
   * @param array $values
   *   Values.
   *
   * @return string
   *   The CSV.
   */
  public function buildQuery(array $values);

}
