<?php

namespace Drupal\basic_ncbi\pmc;

use Drupal\basic_ncbi\NcbiConnector;
use Drupal\basic_ncbi\NcbiDbServiceBase;

/**
 * PMC Service class.
 */
class PmcService extends NcbiDbServiceBase {

  /**
   * GetPubMedArticle constructor.
   *
   * @param \Drupal\basic_ncbi\NcbiConnector $ncbiConnector
   *   NcbiConnector service.
   */
  public function __construct(NcbiConnector $ncbiConnector) {
    $this->ncbiConnector = $ncbiConnector;
    $this->db = 'pmc';
  }

  /**
   * Get Multiple Articles from PubMed ids.
   *
   * @param array $ids
   *   Array of PMC ids.
   *
   * @return array|null
   *   Array of PMC Article.
   */
  public function getMultipleArticleById(array $ids) {

    $xml = $this->getArtcleFromDatabase($ids);

    // @TODO : Create all classes for extracting data from XML.
    /*
    $articles = [];
    foreach ($xml->children() as $xml_article) {
    $articles[] = new PmcArticle($xml_article);
    }
    return $articles;
     */
    return NULL;
  }

}
