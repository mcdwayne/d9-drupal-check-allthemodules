<?php

namespace Drupal\basic_ncbi\pubmed;

use SimpleXMLElement;

/**
 * Class PubMedJournal.
 */
class PubMedJournal {
  private $issn;
  private $title;
  private $iso;
  private $volume;
  private $issue;
  private $date;

  /**
   * PubMedJournal constructor.
   *
   * @param \SimpleXMLElement $xml_journal
   *   Journal definition Xml Fragment.
   */
  public function __construct(SimpleXMLElement $xml_journal) {
    $this->issn = $xml_journal->ISSN->__toString();
    $this->title = $xml_journal->Title->__toString();
    $this->iso = $xml_journal->ISOAbbreviation->__toString();
    $this->volume = $xml_journal->JournalIssue->Volume->__toString();
    $this->issue = $xml_journal->JournalIssue->Issue->__toString();
    $this->date['Year'] = $xml_journal->JournalIssue->PubDate->Year->__toString();
    $this->date['Month'] = $xml_journal->JournalIssue->PubDate->Month->__toString();
    $this->date['Day'] = $xml_journal->JournalIssue->PubDate->Day->__toString();
  }

  /**
   * Return Object as array.
   */
  public function toArray() {
    $output = [];
    $output['ISSN'] = $this->issn;
    $output['Title'] = $this->title;
    $output['ISO'] = $this->iso;
    $output['Volume'] = $this->volume;
    $output['Issue'] = $this->issue;
    $output['Date'] = $this->date;
    return $output;
  }

}
