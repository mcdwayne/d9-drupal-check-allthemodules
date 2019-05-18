<?php

namespace Drupal\basic_ncbi\pubmed;

use Drupal\basic_ncbi\NcbiConnector;
use Drupal\basic_ncbi\NcbiDbServiceBase;

/**
 * PubMedService class.
 */
class PubMedService extends NcbiDbServiceBase {

  /**
   * GetPubMedArticle constructor.
   *
   * @param \Drupal\basic_ncbi\NcbiConnector $ncbiConnector
   *   NcbiConnector service.
   */
  public function __construct(NcbiConnector $ncbiConnector) {
    $this->ncbiConnector = $ncbiConnector;
    $this->db = 'pubmed';
  }

  /**
   * Get Multiple Articles from PubMed ids.
   *
   * @param array $ids
   *   Array of PubMed ids.
   *
   * @return array|null
   *   Array of PubMedArticle.
   */
  public function getMultipleArticleById(array $ids) {

    $xml = $this->getArtcleFromDatabase($ids);

    $articles = [];
    foreach ($xml->children() as $xml_article) {
      $articles[] = new PubmedArticle($xml_article);
    }
    return $articles;
  }

}
