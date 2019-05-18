<?php

namespace Drupal\basic_ncbi;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class NcbiConnector.
 */
class NcbiConnector {
  use StringTranslationTrait;
  /**
   * Guzzle Client.
   *
   * @var \GuzzleHttp\Client
   */
  private $client;


  private $EndPointUrl = "https://eutils.ncbi.nlm.nih.gov/entrez/";

  /**
   * Constructs a new NcbiConnector object.
   */
  public function __construct() {
    $this->client = new Client();
  }

  /**
   * Fetch documents.
   *
   * @param string $db
   *   Database to request.
   * @param array $ids
   *   Array of Ids.
   *
   * @return null|\SimpleXMLElement
   *   SimpleXMLElement object.
   */
  public function fetch($db, array $ids) {
    $id_string = implode(',', $ids);

    $url = "eutils/efetch.fcgi?db=" . $db . "&id=" . $id_string . "&retmode=xml";

    $res = $this->query($url);

    if ($res->getStatusCode() != 200) {
      \Drupal::logger('NCBI Connector')->warning($this->t('Cannot fetch %ids from %db (status %code).',
        ["%ids" => $id_string, "%db" => $db, "%code" => $res->getStatusCode()])
      );
      return NULL;
    }

    if ($res->getBody()->getSize() <= 0) {
      \Drupal::logger('NCBI Connector')->warning($this->t('Cannot fetch %ids from %db (empty response).', ["%ids" => $id_string, "%db" => $db]));
      return NULL;
    }

    $xml_string = $res->getBody()->getContents();
    return simplexml_load_string($xml_string);
  }

  /**
   * Search documents.
   *
   * @param string $db
   *   Database to request.
   * @param string $query
   *   Query to search.
   * @param int $nb
   *   Number of results.
   * @param int $start
   *   Index of start.
   *
   * @return null|\SimpleXMLElement
   *   SimpleXMLElement object.
   */
  public function search($db, $query, $nb = 20, $start = 0) {
    $url = "eutils/esearch.fcgi?db=" . $db . "&term=" . urlencode($query) . "&retmode=xml";
    if ($nb != 20) {
      $url .= "&retmax=" . $nb;
    }
    if ($start != 0) {
      $url .= "&retstart=" . $start;
    }

    $res = $this->query($url);

    if ($res->getStatusCode() != 200) {
      \Drupal::logger('NCBI Connector')->warning($this->t('Cannot search for %query from %db (status %code).',
        ["%query" => $query, "%db" => $db, "%code" => $res->getStatusCode()])
      );
      return NULL;
    }

    if ($res->getBody()->getSize() <= 0) {
      \Drupal::logger('NCBI Connector')->warning($this->t('Cannot search %query from %db (empty response).', ["%query" => $query, "%db" => $db]));
      return NULL;
    }

    $xml_string = $res->getBody()->getContents();
    $xml = simplexml_load_string($xml_string);
    return $xml;
  }

  /**
   * Execute query to NCBI API.
   *
   * @param string $url
   *   URL to request.
   *
   * @return null|\Psr\Http\Message\ResponseInterface
   *   Guzzle response.
   */
  private function query($url) {

    try {
      $res = $this->client->get($this->EndPointUrl . $url, ['http_errors' => FALSE]);
    }
    catch (RequestException $e) {
      \Drupal::logger('NCBI Connector')->error($e->getMessage());
      return NULL;
    }

    return $res;
  }

}
