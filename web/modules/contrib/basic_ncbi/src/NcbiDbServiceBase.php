<?php

namespace Drupal\basic_ncbi;

/**
 * Class NcbiDbServiceBase.
 */
abstract class NcbiDbServiceBase {

  /**
   * DB used for service.
   *
   * @var string*/
  protected $db;

  /**
   * Drupal\basic_ncbi\NcbiConnector definition.
   *
   * @var \Drupal\basic_ncbi\NcbiConnector
   */
  protected $ncbiConnector;

  /**
   * Get a single Article from PubMed Id.
   *
   * @param int $pmid
   *   A single id.
   *
   * @return mixed
   *   A single Article.
   */
  public function getArticleById($pmid) {
    return $this->getMultipleArticleById([$pmid])[0];
  }

  /**
   * Get Multiple articles from Ids.
   *
   * @param array $ids
   *   Array of ids.
   *
   * @return array
   *   Array of Articles.
   */
  abstract public function getMultipleArticleById(array $ids);

  /**
   * Make search request.
   *
   * @param array $ids
   *   Array of ids.
   *
   * @return null|\SimpleXMLElement
   *   Null or SimpleXMLElement.
   */
  public function getArtcleFromDatabase(array $ids) {
    /** @var \SimpleXMLElement $xml */
    $xml = $this->ncbiConnector->fetch($this->db, $ids);
    if ($xml == NULL) {
      return NULL;
    }
    return $xml;
  }

  /**
   * Search ID from query.
   *
   * @param string $query
   *   Text Query.
   * @param int $nb
   *   Number of results.
   * @param int $start
   *   Index of start.
   *
   * @return array
   *   List of results.
   */
  public function search($query, $nb = 20, $start = 0) {
    $xml = $this->ncbiConnector->search($this->db, $query, $nb, $start);

    $results = [];
    foreach ($xml->IdList->children() as $id) {
      $results[] = $id->__toString();
    }

    $output = [
      'results_count' => $xml->Count->__toString(),
      'results_max' => $xml->RetMax->__toString(),
      'results_start' => $xml->RetStart->__toString(),
      'results' => $results,
    ];

    return $output;
  }

}
