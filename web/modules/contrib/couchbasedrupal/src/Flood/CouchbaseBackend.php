<?php

/**
 * @file
 * Contains \Drupal\couchbasedrupal\Flood\CouchbaseBackend.
 */

namespace Drupal\couchbasedrupal\Flood;

use Drupal\Core\Flood\FloodInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Database\Connection;
use Couchbase\N1qlQuery as CouchbaseN1qlQuery;
use Drupal\couchbasedrupal\CouchbaseManager;

/**
 * Defines the database flood backend. This is the default Drupal backend.
 *
 * TODO: We need better indexes + make sure that
 * there are no collisions between sites by using
 * a site prefix.
 *
 */
class CouchbaseBackend implements FloodInterface {

  /**
   * The bucket to use to store the information.
   *
   * @var \Drupal\couchbasedrupal\CouchbaseBucket
   */
  protected $bucket;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;


  /**
   * Summary of $guid_generator
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $guid_generator;

  /**
   * Site prefix.
   *
   * @var mixed
   */
  protected $sitePrefix;

  /**
   * Construct the DatabaseBackend.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection which will be used to store the flood event
   *   information.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack used to retrieve the current request.
   */
  public function __construct(CouchbaseManager $manager, RequestStack $request_stack) {
    $this->bucket = $manager->getBucketFromConfig('default');
    $this->requestStack = $request_stack;
    $this->guid_generator = new \Drupal\Component\Uuid\Com();
    $this->sitePrefix = $manager->getSitePrefix();
  }

  /**
   * {@inheritdoc}
   */
  public function register($name, $window = 3600, $identifier = NULL) {
    if (!isset($identifier)) {
      $identifier = $this->requestStack->getCurrentRequest()->getClientIp();
    }
    $expire = REQUEST_TIME + $window;
    $this->bucket->insert("{$this->sitePrefix}_flood_" . $this->guid_generator->generate(), [
      'event' => $name,
      'identifier' => $identifier,
      'timestamp' => REQUEST_TIME
    ], array('expiry' => $expire));
  }

  /**
   * {@inheritdoc}
   */
  public function clear($name, $identifier = NULL) {
    if (!isset($identifier)) {
      $identifier = $this->requestStack->getCurrentRequest()->getClientIp();
    }
    $query = "DELETE FROM {$this->bucket->getName()} WHERE event = '$name' AND identifier = '$identifier' AND META({$this->bucket->getName()}).id LIKE \"{$this->sitePrefix}_flood_%\"";
    $q = CouchbaseN1qlQuery::fromString($query);
    $this->bucket->queryN1QL($q, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function isAllowed($name, $threshold, $window = 3600, $identifier = NULL) {
    if (!isset($identifier)) {
      $identifier = $this->requestStack->getCurrentRequest()->getClientIp();
    }
    // TODO: Use placeholder here!
    $limit = REQUEST_TIME - $window;
    $query = "SELECT COUNT(*) AS RESULT FROM {$this->bucket->getName()} WHERE event = '$name' AND identifier = '$identifier' AND timestamp > $limit AND META({$this->bucket->getName()}).id LIKE \"{$this->sitePrefix}_flood_%\"";
    $q = CouchbaseN1qlQuery::fromString($query);
    $result = (array) $this->bucket->queryN1QL($q, TRUE);
    $row = (array) (isset($result['rows']) ? $result['rows'][0] : $result[0]);
    return $row['RESULT'] < $threshold;
  }

  public function garbageCollection() {
  }
}
