<?php

namespace Drupal\collmex\Plugin\migrate\destination;

abstract class CollmexMultipleItemsBase extends CollmexBase {

  /**
   * @inheritDoc
   */
  protected function buildCsv($values, $oldDestinationIdValues, &$newDestinationIdValues, &$isRenaming) {
    $collectedCsvs = [];
    $collectedNewDestinationIdValues = [];
    if (!isset($values['items']) || !is_array($values['items'])) {
      throw new \UnexpectedValueException('Items must be an array.');
    }
    if (!$values['items']) {
      throw new \UnexpectedValueException('Items must not be empty.');
    }
    foreach ($values['items'] as $values) {
      if (!is_array($values)) {
        throw new \UnexpectedValueException('Each item must be an array.');
      }
      $collectedCsvs[] = parent::buildCsv($values, $oldDestinationIdValues, $newDestinationIdValues, $isRenaming);
      $collectedNewDestinationIdValues[] = $newDestinationIdValues;
    }
    $collectedNewDestinationIdValues = array_unique($collectedNewDestinationIdValues);
    if (count($collectedNewDestinationIdValues) > 1) {
      throw new \UnexpectedValueException(sprintf('Inconsistent destination ID values: %s', print_r($collectedNewDestinationIdValues, 1)));
    }
    $newDestinationIdValues = $collectedNewDestinationIdValues[0];
    // Use '=' comparison to recognize equal int/strings.
    $isRenaming = $oldDestinationIdValues && $oldDestinationIdValues != $newDestinationIdValues;
    $csv = implode('', $collectedCsvs);
    return $csv;
  }

}
