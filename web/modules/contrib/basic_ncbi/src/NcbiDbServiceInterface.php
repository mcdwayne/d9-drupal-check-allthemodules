<?php

namespace Drupal\basic_ncbi;

/**
 * Interface NcbiDbServiceInterface.
 */
interface NcbiDbServiceInterface {

  /**
   * GetPubMedArticle constructor.
   *
   * @param \Drupal\basic_ncbi\NcbiConnector $ncbiConnector
   *   NcbiConnector service.
   */
  public function __construct(NcbiConnector $ncbiConnector);

  /**
   * Get A single document by Id.
   */
  public function getArticleById($id);

  /**
   * Get Multiple document by Id.
   */
  public function getMultipleArticleById(array $ids);

  /**
   * Search PubMedID from query.
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
  public function search($query, $nb = 20, $start = 0);

}
