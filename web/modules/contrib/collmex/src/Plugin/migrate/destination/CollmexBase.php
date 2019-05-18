<?php

namespace Drupal\collmex\Plugin\migrate\destination;

use Drupal\collmex\CollmexMessenger;
use Drupal\collmex\CsvBuilder\ImportCsvBuilderInterface;
use Drupal\collmex\CollmexSender;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;

abstract class CollmexBase extends DestinationBase {

  /**
   * @return \Drupal\collmex\CsvBuilder\ImportCsvBuilderInterface
   */
  abstract protected function getCsvBuilder();

  /**
   * Import.
   *
   * @param \Drupal\migrate\Row $row
   * @param array $oldDestinationIdValues
   *
   * @return array
   *   Remote ID values.
   *
   * @throws \MarcusJaschen\Collmex\Client\Exception\RequestFailedException
   * @throws \MarcusJaschen\Collmex\Exception\InvalidResponseMimeTypeException
   * @throws \MarcusJaschen\Collmex\Exception\InvalidTypeIdentifierException
   */
  public function import(Row $row, array $oldDestinationIdValues = []) {
    $oldDestinationIdValues = array_filter($oldDestinationIdValues, function ($v) {
      return isset($v); // Note that isset is a builtin, not a function.
    });
    $messenger = new CollmexMessenger($this->migration, $row->getSourceIdValues());
    $sender = new CollmexSender($messenger);
    $values = $row->getDestination()
      + array_fill_keys($row->getEmptyDestinationProperties(), '');
    $csv = $this->buildCsv($values, $oldDestinationIdValues, $newDestinationIdValues, $isRenaming);
    $sender->addCsv($csv);
    if ($isRenaming) {
      $this->doRollback($oldDestinationIdValues, $messenger);
    }
    // Send.
    $records = $sender->send();
    // Returned IDs always have precedence.
    $remoteIds = $sender->extractRemoteIds($records);
    if ($remoteIds) {
      if ($newDestinationIdValues) {
        throw new \UnexpectedValueException(sprintf('Passed IDs %s, but got %s', print_r($oldDestinationIdValues, 1), print_r($remoteIds, 1)));
      }
      // Collmex only knows one ID key.
      $newDestinationIdValues = [$remoteIds[0]];
    }
    return $newDestinationIdValues;
  }

  /**
   * Build CSV.
   *
   * @param $values
   *   The values.
   * @param array $oldDestinationIdValues
   *   The old destination ID values.
   * @param $newDestinationIdValues
   *   The new destination ID values, returned by reference.
   * @param $isRenaming
   *   If this is renaming, returned by reference.
   *
   * @return string
   *   The CSV string.
   */
  protected function buildCsv($values, $oldDestinationIdValues, &$newDestinationIdValues, &$isRenaming) {
    // Allow dummy values without freaking out.
    $values = array_filter($values, function ($k) {
      return substr($k, 0, 1) !== '_';
    }, ARRAY_FILTER_USE_KEY);
    $csvBuilder = $this->getCsvBuilder();
    $passedDestinationIdValues = array_values(array_intersect_key($values, array_fill_keys($csvBuilder->getIdKeys(), TRUE)));
    if (empty($passedDestinationIdValues[0]) || $passedDestinationIdValues[0] < 0) {
      if ($oldDestinationIdValues) {
        // If the passed ID is not a regular ID (empty or a placeholder < 0),
        // AND we already have a remote ID, use that to not create another item.
        $values = array_combine($this->getCsvBuilder()->getIdKeys(), $oldDestinationIdValues) + $values;
        $newDestinationIdValues = $oldDestinationIdValues;
        $isRenaming = FALSE;
      }
      else {
        // IDs must come from collmex response.
        $newDestinationIdValues = [];
        $isRenaming = FALSE;
      }
    }
    else {
      // Passed IDs are regular, not placeholders. For such objects, collmex
      // does not provide a created-response, so set new IDs here.
      $newDestinationIdValues = $passedDestinationIdValues;
      // Use '=' comparison to recognize equal int/strings.
      $isRenaming = $oldDestinationIdValues && $oldDestinationIdValues != $newDestinationIdValues;
    }
    $csv = $csvBuilder->buildImport($values);
    return $csv;
  }

  /**
   * Rollback.
   *
   * @param array $destinationIdValues
   *
   * @throws \MarcusJaschen\Collmex\Client\Exception\RequestFailedException
   * @throws \MarcusJaschen\Collmex\Exception\InvalidResponseMimeTypeException
   * @throws \MarcusJaschen\Collmex\Exception\InvalidTypeIdentifierException
   */
  public function rollback(array $destinationIdValues) {
    $sourceIdValues = $this->migration->getIdMap()->lookupSourceId($destinationIdValues);
    $messenger = new CollmexMessenger($this->migration, $sourceIdValues);
    $this->doRollback($destinationIdValues, $messenger);
  }

  /**
   * Do the rollback.
   *
   * Due to migrate WTF, there are different ways to get the source ID values
   * and we have to do the rename rollback different that real rollback.
   *
   * @param array $destinationIdValues
   * @param \Drupal\collmex\CollmexMessenger $messenger
   *
   * @throws \MarcusJaschen\Collmex\Client\Exception\RequestFailedException
   * @throws \MarcusJaschen\Collmex\Exception\InvalidResponseMimeTypeException
   * @throws \MarcusJaschen\Collmex\Exception\InvalidTypeIdentifierException
   */
  protected function doRollback(array $destinationIdValues, CollmexMessenger $messenger) {
    $sender = new CollmexSender($messenger);
    $csv = $this->getCsvBuilder()->buildRollback($destinationIdValues);
    $sender->addCsv($csv);
    $sender->send();
  }

  /**
   * @inheritDoc
   */
  public function fields(MigrationInterface $migration = NULL) {
    return $this->getCsvBuilder()->getFields();
  }

  public function getIds() {
    $result = [];
    $csvBuilder = $this->getCsvBuilder();
    foreach ($csvBuilder->getIdKeys() as $key) {
      $type = $csvBuilder->getFieldType($key);
      $len = $csvBuilder->getFieldLength($key);
      // http://www.collmex.de/cgi-bin/cgi.exe?1006,1,help,daten_importieren_datentypen_felder
      switch ($type) {
        case ImportCsvBuilderInterface::TYPE_CHAR:
          $result[$key] = [
            'type' => 'string',
            'max_length' => $len,
          ];
          break;
        case ImportCsvBuilderInterface::TYPE_INT:
          $result[$key] = [
            'type' => 'decimal',
            'precision' => $len,
            'scale' => 0,
          ];
          break;
        case ImportCsvBuilderInterface::TYPE_NUM:
          $result[$key] = [
            'type' => 'decimal',
            'precision' => $len,
            'scale' => 3,
          ];
          break;
        case ImportCsvBuilderInterface::TYPE_DATE:
          $result[$key] = [
            'type' => 'datetime',
            'datetime_type' => DateTimeItem::DATETIME_TYPE_DATE,
          ];
          break;
        case ImportCsvBuilderInterface::TYPE_MONEY:
          $result[$key] = [
            'type' => 'decimal',
            'precision' => $len,
            'scale' => 2,
          ];
          break;
        case ImportCsvBuilderInterface::TYPE_TIME:
          // Core has no time field. Look how this works out.
          $result[$key] = [
            'type' => 'decimal',
            'precision' => 6,
            'scale' => 0,
          ];
          break;
        default:
          throw new \LogicException("Invalid collmex definition: $key: $type$len");
      }
    }
    return $result;
  }

}
