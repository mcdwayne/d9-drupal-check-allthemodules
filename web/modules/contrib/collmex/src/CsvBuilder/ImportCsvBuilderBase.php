<?php

namespace Drupal\collmex\CsvBuilder;

abstract class ImportCsvBuilderBase extends BaseCsvBuilderBase implements ImportCsvBuilderInterface {

  public function buildImport(array $values) {
    return $this->makeCsv($values);
  }

  public function buildRollback(array $destinationIdValues) {
    // If there is no delete mark, then empty rows will trigger real delete.
    // http://www.collmex.de/cgi-bin/cgi.exe?1006,1,help,daten_importieren_periodische_rechnung
    $values = array_filter($destinationIdValues) ? array_combine($this->getIdKeys(), $destinationIdValues) : [];
    $values += $this->getDeleteMarkValues();
    return $this->makeCsv($values);
  }

}
