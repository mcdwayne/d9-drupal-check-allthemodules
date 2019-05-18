<?php

namespace Drupal\collmex\CsvBuilder;

abstract class QueryCsvBuilderBase extends BaseCsvBuilderBase implements QueryCsvBuilderInterface {

  /**
   * @inheritDoc
   */
  public function buildQuery(array $values) {
    return $this->makeCsv($values);
  }

}
