<?php

namespace Drupal\basic_ncbi\pubmed;

use Drupal\basic_ncbi\NcbiDocumentBase;
use SimpleXMLElement;

/**
 * Class PubmedArticle.
 */
class PubmedArticle extends NcbiDocumentBase {
  private $pmid = '';
  private $title = '';
  private $language = '';
  private $abstract = [];
  private $journal = NULL;
  private $authors = [];
  private $date = [];
  private $keywords = [];
  private $ids = [];
  private $location = [];

  /**
   * PubmedArticle constructor.
   *
   * @param \SimpleXMLElement $xml_pubmed_article
   *   Article definition Xml Fragment.
   */
  public function __construct(SimpleXMLElement $xml_pubmed_article) {
    // Get Ids.
    $xml_article_id_list = $xml_pubmed_article->PubmedData->ArticleIdList;
    foreach ($xml_article_id_list->children() as $xml_id_list) {
      $this->ids[$xml_id_list['IdType']->__toString()] = $xml_id_list[0]->__toString();
    }

    // Get Key Words.
    $xml_medline_citation = $xml_pubmed_article->MedlineCitation;
    foreach ($xml_medline_citation->KeywordList->children() as $keyword) {
      $this->keywords[] = $keyword->__toString();
    }

    $this->pmid = $xml_medline_citation->PMID->__toString();

    // Get Article Data.
    $xml_article = $xml_medline_citation->Article;
    $this->title = $xml_article->ArticleTitle->__toString();

    $this->language = $xml_article->Language->__toString();

    // Get Abstract.
    foreach ($xml_article->Abstract->children() as $item) {
      if (isset($item['Label'])) {
        $this->abstract[$item['Label']->__toString()] = $item->__toString();
      }
      else {
        $this->abstract[] = $item->__toString();
      }

    }

    // Get Location.
    $xml_elocation = $xml_article->ELocationID;
    $location = [];
    if (trim($xml_elocation->__toString()) != '') {
      $location['type'] = $xml_elocation['EIdType']->__toString();
      $location['valid'] = ($xml_elocation['ValidYN']->__toString() == 'Y') ? TRUE : FALSE;
      $location['id'] = $xml_elocation->__toString();
      if ($location['valid'] === TRUE) {
        $e_location = $this->getLocationLink($location['id']);
        if ($e_location != NULL) {
          $location['link'] = $e_location;
          $this->location = $location;
        }
      }
    }

    // Get Journal Data.
    $xml_journal = $xml_article->Journal;
    $this->journal = new PubMedJournal($xml_journal);

    // Get Authors.
    foreach ($xml_article->AuthorList->children() as $xml_author) {
      $author = new PubMedAuthor($xml_author);
      $this->authors[] = $author;
    }

    // Get Date.
    if ($xml_article->ArticleDate->Year != NULL) {
      $this->date['Year'] = $xml_article->ArticleDate->Year->__toString();
      $this->date['Month'] = $xml_article->ArticleDate->Month->__toString();
      $this->date['Day'] = $xml_article->ArticleDate->Day->__toString();
    }
  }

  /**
   * Return Object as array.
   */
  public function toArray() {
    $output = [];
    $output['pmid'] = $this->pmid;
    $output['title'] = $this->title;
    $output['language'] = $this->language;
    $output['abstract'] = $this->abstract;
    $output['journal'] = $this->journal->toArray();
    $authors = [];

    foreach ($this->authors as $author) {
      $authors[] = $author->toArray();
    }
    $output['authors'] = $authors;
    $output['date'] = $this->date;
    $output['keywords'] = $this->keywords;
    $output['E-Location'] = $this->location;
    $output['ids'] = $this->ids;
    return $output;
  }

  /**
   * Get E-Location of File. @TODO : Dirty Solution.
   *
   * @param string $doi_id
   *   Possible Document id.
   *
   * @return null|string
   *   Null or URL of document
   */
  private function getLocationLink($doi_id) {
    $possible_location = 'https://pophealthmetrics.biomedcentral.com/track/pdf/' . $doi_id;
    $headers = @get_headers($possible_location);

    if ($headers[0] == 'HTTP/1.1 200 OK') {
      return $possible_location;
    }

    if ($headers[0] == 'HTTP/1.1 301 Moved Permanently') {
      $location = $headers[4];
      return str_replace('Location: ', '', $location);
    }

    if ($headers[0] == 'HTTP/1.1 404 Not Found') {
      return NULL;
    }

    return NULL;
  }

}
