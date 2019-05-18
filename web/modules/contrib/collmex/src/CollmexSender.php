<?php

namespace Drupal\collmex;

use MarcusJaschen\Collmex\Filter\Windows1252ToUtf8;
use MarcusJaschen\Collmex\Request;
use MarcusJaschen\Collmex\Response\CsvResponse;
use MarcusJaschen\Collmex\Type\Message;
use MarcusJaschen\Collmex\Type\NewObject;
use MarcusJaschen\Collmex\Type\TypeInterface;

class CollmexSender {

  const COLLMEX_SUCCESS = 'S';
  const COLLMEX_WARNING = 'W';
  const COLLMEX_ERROR = 'E';

  /** @var \MarcusJaschen\Collmex\Type\TypeInterface[] */
  protected $csvParts = [];

  /** @var \Drupal\collmex\CollmexMessenger */
  protected $messenger;

  /**
   * CollmexSender constructor.
   *
   * @param \Drupal\collmex\CollmexMessenger $messenger
   */
  public function __construct(CollmexMessenger $messenger) {
    $this->messenger = $messenger;
  }

  /**
   * @param string $csv
   */
  public function addCsv($csv) {
    $this->csvParts[] = $csv;
  }

  /**
   * @return \MarcusJaschen\Collmex\Type\TypeInterface[]
   *   The returned records.
   * @throws \MarcusJaschen\Collmex\Client\Exception\RequestFailedException
   * @throws \MarcusJaschen\Collmex\Exception\InvalidResponseMimeTypeException
   * @throws \MarcusJaschen\Collmex\Exception\InvalidTypeIdentifierException
   */
  public function send() {
    $collmexClient = $this->getClient();
    $collmexRequest = new Request($collmexClient);

    $csv = implode('', $this->csvParts);
    $this->messenger->saveDebugMessage(sprintf("DEBUG REQUEST\n%s\n\n", $csv));
    $collmexResponse = $collmexRequest->send($csv);

    if ($collmexResponse instanceof CsvResponse) {
      if ($collmexResponse->isError()) {
        throw new \LogicException(sprintf('Response error L%s: %s', $collmexResponse->getErrorLine(), $collmexResponse->getErrorMessage()));
      }
      // @see \MarcusJaschen\Collmex\Response\CsvResponse::convertEncodingFromCollmex
      $convertedResponse = (new Windows1252ToUtf8())->filter($collmexResponse->getResponseRaw());
      $this->messenger->saveDebugMessage(sprintf("DEBUG RESPONSE\n%s\n\n", $convertedResponse));
      // @todo Upstream: AbstractType should implement TypeInterface.
      /** @var \MarcusJaschen\Collmex\Type\TypeInterface[] $records */
      $records = $collmexResponse->getRecords();
    }
    else {
      throw new \LogicException(sprintf('Collmex returned an unknown response: %s', var_export($collmexResponse, 1)));
    }

    return $records;
  }

  /**
   * Extract remote IDs.
   *
   * @param \MarcusJaschen\Collmex\Type\TypeInterface[] $records
   *   The raw records.
   * @return array
   *   Remote IDs.
   */
  public function extractRemoteIds($records) {
    $objectRecords = $this->getObjectRecords($records, NewObject::class);
    $remoteIds = [];
    /** @var \MarcusJaschen\Collmex\Type\NewObject $objectRecord */
    foreach ($objectRecords as $objectRecord) {
      $remoteId = $objectRecord->new_id;
      $remoteIds[] = $remoteId;
      // Note that line 1 is login.
      $index = @$objectRecord->line - 2;
      if (!$remoteId) {
        throw new \LogicException(sprintf('Collmex returned cryptic new-object record: %s', var_export($objectRecord, 1)));
      }
      $this->messenger->saveDebugMessage(sprintf('DEBUG NEW-ID: %s for object #%s.', $remoteId, $index));
    }
    return $remoteIds;
  }

  /**
   * @param \MarcusJaschen\Collmex\Type\TypeInterface[] $records
   *   The raw records.
   * @param null $expectedClass
   *   The expected class.
   * @return array
   *   Records of expected class.
   */
  public function getObjectRecords($records, $expectedClass = NULL) {
    $objectRecords = [];
    foreach ((array) $records as $record) {
      if ($record instanceof Message) {
        // @todo Upstream should null all properties to not raise errors, e.g. on line unset.
        if (isset($record->message_type)) {
          $messageType = $record->message_type;
        }
        else {
          throw new \LogicException(sprintf('Collmex returned message without type: %s', var_export($record->getData(), 1)));
        }
        if ($messageType == static::COLLMEX_ERROR) {
          throw new \LogicException(sprintf('Collmex returned error: %s', $record->message_text));
        }
        elseif ($messageType == static::COLLMEX_WARNING) {
          throw new \LogicException(sprintf('Collmex returned error: %s', $record->message_text));
        }
        $this->messenger->saveDebugMessage(sprintf('DEBUG record: %s.', var_export($record->getData(), 1)));
      }
      if ($record instanceof TypeInterface) {
        if ($record instanceof $expectedClass) {
          $objectRecords[] = $record;
        }
      }
      else {
        throw new \LogicException(sprintf('Collmex returned an unknown record: %s', var_export($record, 1)));
      }
    }
    return $objectRecords;
  }

  protected function getClient() {
    $config = \Drupal::config('collmex.settings');
    return new CurlWrapper(
      $config->get('user'),
      $config->get('password'),
      $config->get('customer'),
      $config->get('dryrun')
    );
  }

}
