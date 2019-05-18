<?php

namespace Drupal\collmex\Query;

use Drupal\collmex\CollmexMessenger;
use Drupal\collmex\CollmexSender;
use Drupal\collmex\CsvBuilder\QueryMembersCsvBuilder;
use MarcusJaschen\Collmex\Type\Member;

class CollmexMemberQuery {

  /** @var \Drupal\collmex\CollmexMessenger*/
  protected $messenger;

  /**
   * CollmexMemberQuery constructor.
   * @param \Drupal\collmex\CollmexMessenger $messenger
   */
  public function __construct(CollmexMessenger $messenger) {
    $this->messenger = $messenger;
  }

  /**
   * Query members by email.
   *
   * @param $email
   *   The email address to query.
   * @return \MarcusJaschen\Collmex\Type\Member[]
   *   The returned member records.
   * @throws \MarcusJaschen\Collmex\Client\Exception\RequestFailedException
   * @throws \MarcusJaschen\Collmex\Exception\InvalidResponseMimeTypeException
   * @throws \MarcusJaschen\Collmex\Exception\InvalidTypeIdentifierException
   */
  public function byEmail($email) {
    $records = $this->queryMembers($email);
    $result = [];
    foreach ($records as $record) {
      if ($record->email === $email) {
        $result[] = $record;
      }
    }
    return $result;
  }

  /**
   * Query members by full text.
   *
   * @param null $text
   *   The text to query.
   * @return \MarcusJaschen\Collmex\Type\Member[]
   *   The returned member records.
   * @throws \MarcusJaschen\Collmex\Client\Exception\RequestFailedException
   * @throws \MarcusJaschen\Collmex\Exception\InvalidResponseMimeTypeException
   * @throws \MarcusJaschen\Collmex\Exception\InvalidTypeIdentifierException
   */
  protected function queryMembers($text = NULL) {
    $sender = new CollmexSender($this->messenger);
    $csvBuilder = new QueryMembersCsvBuilder();
    $csv = $csvBuilder->buildQuery([
      'query' => $text,
      'exited_too' => TRUE,
    ]);
    $sender->addCsv($csv);
    $records = $sender->send();
    $objectRecords = $sender->getObjectRecords($records, Member::class);
    return $objectRecords;
  }

}
