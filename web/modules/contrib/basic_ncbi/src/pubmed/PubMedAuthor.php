<?php

namespace Drupal\basic_ncbi\pubmed;

use Drupal\basic_ncbi\NcbiDocumentBase;
use SimpleXMLElement;

/**
 * Class PubMedAuthor.
 */
class PubMedAuthor extends NcbiDocumentBase {
  private $lastName;
  private $foreName;
  private $initals;
  private $affiliations;

  /**
   * PubMedAuthor constructor.
   *
   * @param \SimpleXMLElement $xml_author
   *   Author definition Xml Fragment.
   */
  public function __construct(SimpleXMLElement $xml_author) {
    $this->lastName = $xml_author->LastName->__toString();
    $this->foreName = $xml_author->ForeName->__toString();
    $this->initals = $xml_author->Initials->__toString();
    foreach ($xml_author->AffiliationInfo as $xml_affiliationInfo) {
      $this->affiliations[] = $xml_affiliationInfo->Affiliation->__toString();
    }
  }

  /**
   * Return Object as array.
   */
  public function toArray() {
    $output = [];
    $output['LastName'] = $this->lastName;
    $output['ForeName'] = $this->foreName;
    $output['Initials'] = $this->initals;
    $output['Affiliations'] = $this->affiliations;
    return $output;
  }

}
