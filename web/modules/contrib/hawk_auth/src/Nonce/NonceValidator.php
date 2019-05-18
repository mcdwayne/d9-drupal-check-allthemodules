<?php

/**
 * @file
 * Contains Drupal\hawk_auth\Nonce\NonceValidator.
 */

namespace Drupal\hawk_auth\Nonce;

use Dragooon\Hawk\Nonce\NonceValidatorInterface;
use Drupal\Core\Cache\DatabaseBackend;
use Drupal\Core\Cache\DatabaseBackendFactory;

/**
 * Validator for nonce values during hawk requests.
 */
class NonceValidator implements NonceValidatorInterface {

  /**
   * Cache (hawk) bin.
   *
   * @var DatabaseBackend $cache
   */
  protected $cache;

  /**
   * Constructs an NonceValidator object.
   *
   * @param DatabaseBackendFactory $cache_factory
   *   Hawk cache bin.
   */
  public function __construct(DatabaseBackendFactory $cache_factory) {
    $this->cache = $cache_factory->get('hawk');
  }

  /**
   * {@inheritdoc}
   */
  public function validateNonce($key, $nonce, $timestamp) {
    // A nonce for a particular client ($key) on a particular timestamp
    // has to be unique.
    $cid = 'nonce:' . $key;

    $valid = TRUE;
    $values = [];
    if ($cache = $this->cache->get($cid)) {
      $values = $cache->data;
    }

    foreach ($values as $k => $value) {
      if ($value['nonce'] == $nonce && $value['timestamp'] == $timestamp) {
        $valid = FALSE;
      }

      if ($value['timestamp'] < time() - 600) {
        unset($values[$k]);
      }
    }

    if ($valid) {
      $values[] = [
        'nonce' => $nonce,
        'timestamp' => $timestamp,
      ];
    }

    $this->cache->set($cid, $values, time() + 3600);

    return $valid;
  }

}
