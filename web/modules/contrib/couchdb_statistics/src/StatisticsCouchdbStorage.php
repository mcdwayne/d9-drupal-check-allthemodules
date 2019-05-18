<?php
/**
 * @file
 * Contains \Drupal\couchdb_statistics\StatisticsCouchdbStorage.
 */

namespace Drupal\couchdb_statistics;

use Drupal\statistics\StatisticsStorageInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Site\Settings;
use Doctrine\CouchDB\CouchDBClient;

/**
 * Provides a CouchDB storage backend for statistics module.
 */
class StatisticsCouchdbStorage implements StatisticsStorageInterface {

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;
  
  /**
   * Settings Service.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected $settings;

  /**
   * The CouchDB client.
   *
   * @var \Doctrine\CouchDB\CouchDBClient::create()
   */
  protected $client;

  /**
  * Construct the statistics storage.
  *
  * @param \Drupal\Core\State\StateInterface $state
  *   The state service.
  * 
  * @param \Drupal\Core\Site\Settings $settings
  *   The read-only settings container.
  */
  public function __construct(StateInterface $state, Settings $settings) {
    $this->state = $state;
    $this->settings = $settings;
    $couchdb_settings = $this->settings->get('couchdb');
    if (!is_array($couchdb_settings)) {
      throw new \RuntimeException('No CouchDB settings set.');
    }
    $this->client = CouchDBClient::create($couchdb_settings);
    
  }

  /**
  * {@inheritdoc}
  */
  public function recordHit($nid) {
    $doc = $this->client->findDocument((string)$nid);
    \Drupal::logger('couchdb_statistics')->notice(print_r($doc,true));
    if ($doc->status == '200') {
      $doc = $this->client->putDocument(array(
        'nid' => $nid,
        'daycount' => (int)$doc->body['daycount'] + 1,
        'totalcount' =>  (int)$doc->body['totalcount'] + 1,
        'timestamp' => REQUEST_TIME,
      ), (string)$nid, $doc->body['_rev']);
    }
    else {
      $doc = $this->client->postDocument(array(
        '_id' => (string)$nid,
        'nid' => $nid,
        'daycount' => 1,
        'totalcount' => 1,
        'timestamp' => REQUEST_TIME,
      ));      
    }
    \Drupal::logger('couchdb_statistics')->notice(print_r($doc,true));
  }

  /**
  * {@inheritdoc}
  */
  public function fetchViews($nid) {
    $doc = $this->client->findDocument((string)$nid);
    if ($doc->status == '200') {
      return $doc->body;
    }
  }

  /**
  * {@inheritdoc}
  */
  public function fetchAll($order = 'totalcount', $limit = 5) {
  }

  /**
  * {@inheritdoc}
  */
  public function clean($nid) {
  }

  /**
  * {@inheritdoc}
  */
  public function needsReset() {
  }

  /**
  * {@inheritdoc}
  */
  public function resetDayCount() {
  }

  /**
  * {@inheritdoc}
  */
  public function maxTotalCount() {
  }

}
