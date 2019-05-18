<?php

/**
 * @file
 * Contains \Drupal\lcache\Backend.
 */

namespace Drupal\lcache;

use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Defines an LCache backend.
 */
class Backend implements CacheBackendInterface {

  /**
   * The cache bin to use.
   *
   * @var string
   */
  protected $bin;

  /**
   * The LCache stack, including L1 and L2.
   *
   * @var LCache\Integrated
   */
  protected $integrated;

  /**
   * Constructs a Backend object.
   *
   * @param string $bin
   *   The bin name.
   */
  public function __construct($bin, \LCache\Integrated $integrated) {
    $this->bin = $bin;
    $this->integrated = $integrated;
  }

  /**
   * Return an Address for a given cid.
   *
   * @param string $cid
   *   The Cache ID.
   */
  protected function getAddress($cid) {
    $cid = $this->normalizeCid($cid);
    return new \LCache\Address($this->bin, $cid);
  }

  /**
   * {@inheritdoc}
   */
  public function get($cid, $allow_invalid = FALSE) {
    $address = $this->getAddress($cid);
    $entry = $this->integrated->getEntry($address);

    if (is_null($entry)) {
      return FALSE;
    }

    $response = new \stdClass();
    $response->cid = $cid;
    $response->valid = TRUE;
    $response->data = $entry->value;
    $response->created = $entry->created;

    // LCache the library uses NULL for permanent
    // but that may confuse parts of Drupal.
    // @todo, investigate if there is a better answer than this munging.
    if (is_null($entry->expiration)) {
      $entry->expiration = CacheBackendInterface::CACHE_PERMANENT;
    }

    $response->expire = $entry->expiration;
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function getMultiple(&$cids, $allow_invalid = FALSE) {
    if (empty($cids)) {
      return;
    }
    $cache = array();
    foreach ($cids as $cid) {
      $c = $this->get($cid);
      if (!empty($c)) {
        $cache[$cid] = $c;
      }
    }
    $cids = array_diff($cids, array_keys($cache));
    return $cache;
  }

  /**
   * {@inheritdoc}
   */
  public function set($cid, $data, $expire = CacheBackendInterface::CACHE_PERMANENT, array $tags = array()) {
    assert('\Drupal\Component\Assertion\Inspector::assertAllStrings($tags)');
    $tags = array_unique($tags);
    // Sort the cache tags so that they are stored consistently.
    sort($tags);

    $address = $this->getAddress($cid);
    $ttl = NULL;
    if ($expire !== CacheBackendInterface::CACHE_PERMANENT) {
      $ttl = $expire - REQUEST_TIME;
    }
    $this->integrated->set($address, $data, $ttl, $tags);
  }

  /**
   * {@inheritdoc}
   */
  public function setMultiple(array $items) {
    foreach ($items as $cid => $item) {
      $item += array(
        'expire' => CacheBackendInterface::CACHE_PERMANENT,
        'tags' => array(),
      );
      $this->set($cid, $item['data'], $item['expire'], $item['tags']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete($cid) {
    $address = $this->getAddress($cid);
    $this->integrated->delete($address);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple(array $cids) {
    foreach ($cids as $cid) {
      $this->delete($cid);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll() {
    $this->delete(NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function invalidate($cid) {
    $this->delete($cid);
  }

  /**
   * Marks cache items as invalid.
   *
   * Invalid items may be returned in later calls to get(), if the
   * $allow_invalid argument is TRUE.
   *
   * @param array $cids
   *   An array of cache IDs to invalidate.
   *
   * @see Drupal\Core\Cache\CacheBackendInterface::deleteMultiple()
   * @see Drupal\Core\Cache\CacheBackendInterface::invalidate()
   * @see Drupal\Core\Cache\CacheBackendInterface::invalidateTags()
   * @see Drupal\Core\Cache\CacheBackendInterface::invalidateAll()
   */
  public function invalidateMultiple(array $cids) {
    foreach ($cids as $cid) {
      $this->invalidate($cid);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateAll() {
    $this->delete(NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function removeBin() {
    $this->invalidateAll();
  }

  /**
   * {@inheritdoc}
   */
  public function garbageCollection() {
    $this->integrated->collectGarbage();
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return FALSE;
  }

  /**
   * Normalizes a cache ID in order to comply with database limitations.
   *
   * @param string $cid
   *   The passed in cache ID.
   *
   * @return string
   *   An ASCII-encoded cache ID that is limited in length.
   */
  protected function normalizeCid($cid) {
    $cid_is_ascii = mb_check_encoding($cid, 'ASCII');

    // 508 is the max length of the address column (512) minus
    // the number of characters that will be added to the stored address value
    // by Address::serialze() when the bin length is long (two digits).
    $max_cid_length = 508 - strlen($this->bin);
    if (strlen($cid) <= $max_cid_length && $cid_is_ascii) {
      return $cid;
    }

    // Return a string that uses as much as possible of the original cache ID
    // with the hash appended.
    // Cut the hash in half to allow for more storage of the real cid's length
    // to be stored.
    $hash = substr(hash('sha512', $cid), 0, 64);
    if (!$cid_is_ascii) {
      return $hash;
    }
    return substr($cid, 0, $max_cid_length - strlen($hash)) . $hash;
  }
}
