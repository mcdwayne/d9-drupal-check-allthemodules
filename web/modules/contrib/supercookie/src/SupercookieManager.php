<?php
namespace Drupal\supercookie;

use Drupal\Core\Site\Settings;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Session\AccountProxy;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;

/**
 * The Supercookie manager class.
 */
class SupercookieManager {

  public $dnt = FALSE;
  public $scid;
  public $uid;
  public $created;
  public $modified;
  public $expires = 0;
  public $data = NULL;
  public $tid = [];
  public $nid = [];
  public $custom = [];

  public $config;
  private $connection;
  private $user;
  private $mongodb;
  private $mongodbConn;

  /**
   * Constructor method.
   */
  public function __construct(Settings $settings, ConfigFactory $config_factory, Connection $connection, AccountProxy $user) {

    $this->connection = $connection;
    $this->user = $user;

    $this->config = $config_factory
      ->get('supercookie.settings')
      ->get();

    $this->mongodb = $this->config['supercookie_mongodb'] && class_exists('\MongoDB\Client');
    $this->mongodbConn = $settings->get('mongodb');
    $this->mongodbConn = $this->mongodbConn['default'];

    return $this;
  }

  /**
   * Gets an instance of the MongoDB collection per config.
   */
  public function getMongoCollection() {

    $collection = NULL;

    if ($this->mongodb) {
      $client = new \MongoDB\Client($this->mongodbConn['host'] . '/' . $this->mongodbConn['db']);
      $collection = $client->selectCollection($this->mongodbConn['db'], 'supercookie');
    }

    return $collection;
  }

  /**
   * Get the custom HTTP header set by supercookie.
   */
  private function getHeader($all = FALSE) {

    if (function_exists('apache_request_headers')) {
      $request_headers = getallheaders();
    }
    else {
      // Nginx equivalent.
      foreach ($_SERVER as $key => $value) {
        if (substr($key, 0, 5) == 'HTTP_') {
          $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
          $request_headers[$key] = $value;
        }
        else {
          $request_headers[$key] = $value;
        }
      }
    }

    if ($all) {
      return $request_headers;
    }

    return (!empty($request_headers[$this->config['supercookie_name_header']]) ? $request_headers[$this->config['supercookie_name_header']] : NULL);
  }

  /**
   * TODO.
   *
   * @see http://php.net/manual/en/function.array-walk-recursive.php#114574
   */
  private function walkRecursiveRemoveNulls(array $array) {
    foreach ($array as $k => $v) {
      if (is_array($v)) {
        $array[$k] = $this->walkRecursiveRemoveNulls($v);
      }
      else {
        if ($v === NULL) {
          unset($array[$k]);
        }
      }
    }

    return $array;
  }

  /**
   * TODO.
   */
  private function init($matcher = NULL, $data = NULL) {

    // Get client's specified hash.
    $hash_client = 0;
    $header = $this->getHeader();
    $headers = $this->getHeader(TRUE);

    // Flag to honor user's (and site owners's acknowledgemnt of) DNT requests.
    $this->dnt = $this->config['supercookie_honor_dnt'] && !empty($headers['DNT']) && $headers['DNT'] == 1;

    // Check custom HTTP header for cookie value.
    if (empty($hash_client) && !empty($header) && $header !== '""') {
      $hash_client = $header;
    }
    // Check Cookie HTTP header for cookie value.
    if (empty($hash_client) && !empty($headers['Cookie'])) {
      $cookies = explode(';', $headers['Cookie']);
      foreach ($cookies as $pair) {
        $parts = explode('=', $pair);
        if ($parts[0] == $this->config['supercookie_name_server']) {
          $hash_client = $parts[1];
        }
      }
    }
    // Check HTTP cookie for cookie value.
    if (empty($hash_client) && !empty($_COOKIE[$this->config['supercookie_name_server']])) {
      $hash_client = $_COOKIE[$this->config['supercookie_name_server']];
    }

    // Expire user's db record and cookies if client hash does not match hash
    // lookup result.
    if (!empty($data) && !empty($hash_client)) {
      if ($data->data !== $hash_client) {
        // TODO: bring back delete op?
      }
    }

    $this->scid = (!empty($data->scid) ? $data->scid : 0);
    $this->data = (!empty($data->data) ? $data->data : $matcher);
    if (empty($this->data)) {
      $this->data = $hash_client;
    }

    return $this->read();
  }

  /**
   * TODO.
   */
  private function read() {

    $or = new Condition('OR');
    $or = $or
      ->condition('scid', $this->scid)
      ->condition('data', $this->data);

    // Honor user's DNT header and delete any previously collected data.
    if ($this->dnt === TRUE) {
      if ($this->mongodb) {
        $this
          ->getMongoCollection()
          ->deleteOne(array('data' => $this->data));
      }
      else {
        $this
          ->connection
          ->delete('supercookie')
          ->condition($or)
          ->execute();
      }

      $this->scid = 0;
      $this->uid = 0;
      $this->created = 0;
      $this->modified = 0;
      $this->expires = 0;
      $this->data = NULL;
      $this->tid = [];
      $this->nid = [];
      $this->custom = [];

      return $this;
    }

    $row = NULL;

    if ($this->mongodb) {
      // Check for mongodb storage.
      if (!empty($this->scid)) {
        $row = (object) $this
          ->getMongoCollection()
          ->findOne(array('_id' => new \MongoDB\BSON\ObjectID($this->scid)));
      }
      else {
        $row = (object) $this
          ->getMongoCollection()
          ->findOne(array(
            '$or' => array(
              array('data' => $this->data),
              array('scid' => $this->scid),
            ),
          ));
      }

      if (!empty($row->_id)) {
        $row->scid = $row->_id->__toString();
      }
    }
    else {
      // Default to standard rdbms.
      $row = $this
        ->connection
        ->select('supercookie', 'sc')
        ->fields('sc', array(
          'scid',
          'uid',
          'created',
          'modified',
          'expires',
          'data',
          'tid',
          'nid',
          'custom',
        ))
        ->condition($or)
        ->range(0, 1)
        ->orderBy('sc.created', 'DESC')
        ->execute()
        ->fetchObject();
    }

    if (!empty($row) && !empty($row->scid)) {
      $this->scid = (!$this->mongodb ? intval($row->scid) : $row->scid);
      $this->uid = intval($row->uid);
      $this->created = intval($row->created);
      $this->modified = intval($row->modified);
      $this->expires = intval($row->expires);
      $this->data = $row->data;
      $this->tid = (!$this->mongodb ? unserialize($row->tid) : (array) $row->tid);
      $this->nid = (!$this->mongodb ? unserialize($row->nid) : (array) $row->nid);
      $this->custom = (!$this->mongodb ? unserialize($row->custom) : (array) $row->custom);
    }
    else {
      $this->scid = 0;
      $this->uid = 0;
      $this->created = 0;
      $this->modified = 0;
      $this->expires = 0;
      // Don't even think about bringing back $this->data = NULL; doing so will
      // cause infinite XHR requests from JS when calls to $this->init() are
      // made with no args.
      $this->tid = [];
      $this->nid = [];
      $this->custom = [];
    }

    return $this;
  }

  /**
   * TODO.
   */
  private function write($timestamp) {

    if ($this->dnt === TRUE) {
      return;
    }

    $row = array(
      'uid' => $this->user->id(),
      'modified' => $timestamp,
      'data' => $this->data,
      'tid' => (is_array($this->tid) ? $this->tid : []),
      'nid' => (is_array($this->nid) ? $this->nid : []),
      'custom' => (is_array($this->custom) ? $this->custom : []),
    );

    if (!empty($this->scid)) {
      $row['scid'] = $this->scid;
    }
    else {
      $row['created'] = $timestamp;
      $row['expires'] = $this->expires;
    }

    if ($this->mongodb) {
      // Check for mongodb storage.
      if (empty($row['scid'])) {
        $this->scid = $this
          ->getMongoCollection()
          ->insertOne($row)
          ->getInsertedId()
          ->__toString();

        $row['scid'] = $this->scid;
      }

      $this
        ->getMongoCollection()
        ->updateOne(array('_id' => new \MongoDB\BSON\ObjectID($row['scid'])), array('$set' => $row));
    }
    elseif (!empty($row['data']) && $row['data'] !== '""') {
      // Default to standard rdbms.
      $row['tid'] = serialize($row['tid']);
      $row['nid'] = serialize($row['nid']);
      $row['custom'] = serialize($row['custom']);

      $this
        ->connection
        ->upsert('supercookie')
        ->key('scid')
        ->fields($row)
        ->execute();
    }

  }

  /**
   * TODO.
   */
  private function delete($timestamp) {

    if ($this->mongodb) {
      $result = $this
        ->getMongoCollection()
        ->deleteOne(array(
          'expired' => $timestamp,
          'scid' => $this->scid,
        ));
    }
    else {
      $result = $this
        ->connection
        ->delete('supercookie')
        ->condition('expires', $timestamp, '<')
        ->condition('scid', $this->scid)
        ->execute();
    }

    return $result;
  }

  /**
   * TODO.
   */
  public function match(&$hash) {

    // Check db for fingerprint match on data.
    if ($this->mongodb) {
      $data = $this
        ->getMongoCollection()
        ->findOne(array(
          'data' => $hash,
        ));

      if (!empty($data['scid'])) {
        $data = (object) array(
          'scid' => $data['scid'],
          'data' => $data['data'],
        );
      }
      else {
        $data = NULL;
      }
    }
    else {
      $data = $this
        ->connection
        ->select('supercookie', 'sc')
        ->fields('sc', array(
          'scid',
          'data',
        ))
        ->condition('data', $hash)
        ->range(0, 1)
        ->orderBy('sc.created', 'DESC')
        ->execute()
        ->fetchObject();
    }

    return $this->init($hash, $data);
  }

  /**
   * TODO.
   */
  public function save($timestamp) {

    // Ignore client time; use server time exclusively.
    $timestamp = REQUEST_TIME;
    $expires = $this->config['supercookie_expire'];
    if ($expires == 'calendar_day') {
      $expires = strtotime(date('Y-m-d', $timestamp) . ' + 1 day');
    }
    else {
      $expires = ($timestamp + $expires);
    }

    if (empty($this->expires)) {
      $this->expires = $expires;
    }

    // Clean up expired sessions.
    if ($this->expires < $timestamp) {
      $expired = $this->delete($timestamp);

      // Reset object and set new expiration.
      if (!empty($expired)) {
        $this->read();
        $this->expires = $expires;
      }
    }

    // Upsert fingerprint record.
    $this->write($timestamp);

    // Return populated supercookie.
    return $this->read();
  }

  /**
   * Update record's target field.
   */
  private function mergeField($field_name, $data) {
    // Merge $data to target field.
    if (empty($this->{$field_name})) {
      $this->{$field_name} = [];
    }

    if (empty($data)) {
      return $this;
    }

    if (in_array($field_name, array('tid', 'nid'))) {
      $data = array_fill_keys($data, 1);
    }
    if (!empty($this->{$field_name})) {
      // Increment value counters for nid and tid fields.
      if (in_array($field_name, array('tid', 'nid'))) {
        foreach ($this->{$field_name} as $key => &$count) {
          if (array_key_exists($key, $data)) {
            $this->{$field_name}[$key] = ($this->{$field_name}[$key] + 1);
            unset($data[$key]);
          }
        }

        foreach ($data as $key => &$count) {
          $this->{$field_name}[$key] = $data[$key];
        }
      }
      elseif ($field_name == 'custom') {
        // Deep merge existing value with input leaves.
        $this->{$field_name} = array_replace_recursive($this->{$field_name}, $data);
        // Now prune NULL leaves from value.
        $this->{$field_name} = $this->walkRecursiveRemoveNulls($this->{$field_name});
      }

      arsort($this->{$field_name});
    }
    else {
      $this->{$field_name} = $data;
    }

    // Return populated supercookie.
    return $this->save($this->created);
  }

  /**
   * Update custom field. To remove a leaf from the array set its value to NULL.
   */
  public function mergeCustom(array $data) {
    return $this->mergeField('custom', $data);
  }

  /**
   * Update record's nid field.
   */
  public function trackNodes(array $data) {
    if ($this->config['supercookie_track_nid']) {
      return $this->mergeField('nid', $data);
    }

    return $this;
  }

  /**
   * Update record's tid field.
   */
  public function trackTerms(array $data) {
    if ($this->config['supercookie_track_tid']) {
      return $this->mergeField('tid', $data);
    }

    return $this;
  }

  /**
   * Gets a human-readable array from raw supercookie values.
   */
  private function reportFormat($object) {

    $account = User::load($object->uid);
    $uname = t('anonymous');
    if ($account->isAuthenticated()) {
      $uname = $account->getAccountName();
    }

    $human = array(
      'user' => $uname,
      'cookie' => $object->scid,
      'hash' => $object->data,
      'created' => format_date($object->created, 'e'),
      'modified' => format_date($object->modified, 'e'),
      'expires' => format_date($object->expires, 'e'),
    );

    // Try to unserialize row blobs.
    if (!$this->mongodb) {
      $tid = unserialize($object->tid);
      $nid = unserialize($object->nid);
      $custom = unserialize($object->custom);

      if ($tid !== FALSE) {
        $object->tid = $tid;
      }
      if ($nid !== FALSE) {
        $object->nid = $nid;
      }
      if ($custom !== FALSE) {
        $object->custom = unserialize($object->custom);
        $human['custom'] = $object->custom;
      }
    }

    // Add term names + counts to response.
    if (!empty($object->tid)) {
      $human['terms'] = Term::loadMultiple(array_keys($object->tid));
      foreach ($human['terms'] as &$term) {
        $term = (object) array(
          $term->getName() => $object->tid[$term->id()],
        );
      }
      $human['terms'] = array_values($human['terms']);
    }

    // Add node titles + counts to response.
    if (!empty($object->nid)) {
      $human['nodes'] = Node::loadMultiple(array_keys($object->nid));
      foreach ($human['nodes'] as &$node) {
        $node = (object) array(
          $node->getTitle() => $object->nid[$node->id()],
        );
      }
      $human['nodes'] = array_values($human['nodes']);
    }

    return $human;
  }

  /**
   * Dump a JSON blob of all current, transformed supercookie data.
   */
  public function report() {

    $data = [];

    if ($this->mongodb) {
      $results = $this
        ->getMongoCollection()
        ->find();

      $iterator = new \IteratorIterator($results);
      $iterator->rewind();
      while ($row = $iterator->current()) {
        $row = $row->bsonSerialize();
        $row->tid = (array) $row->tid->bsonSerialize();
        $row->nid = (array) $row->nid->bsonSerialize();
        $row->custom = (array) $row->custom->bsonSerialize();

        $data[] = $row;
        $iterator->next();
      }

      foreach ($data as &$human) {
        $human = $this->reportFormat($human);

        // Allow other modules to customize each row as needed (e.g. per its own
        // usage of the "custom" field).
        \Drupal::moduleHandler()->alter('supercookie.admin_report', $human);
      }
    }
    else {
      $results = $this
        ->connection
        ->select('supercookie', 'sc')
        ->fields('sc', array(
          'scid',
          'uid',
          'created',
          'modified',
          'expires',
          'data',
          'tid',
          'nid',
          'custom',
        ))
        ->execute();

      while ($row = $results->fetch()) {
        $human = $this->reportFormat($row);

        // Allow other modules to customize each row as needed (e.g. per its own
        // usage of the "custom" field).
        \Drupal::moduleHandler()->alter('supercookie.admin_report', $human);

        $data[] = $human;
      }
    }

    return $data;
  }

}
