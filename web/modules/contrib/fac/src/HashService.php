<?php

namespace Drupal\fac;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\State\StateInterface;

/**
 * Class HashService.
 *
 * Provides a Fast Autocomplete hash service. The hash service is used to reduce
 * the risk of information leakage by using a hash in the JSON files URL. This
 * specific implementation uses the user roles when creating a hash.
 *
 * @package Drupal\fac
 */
class HashService implements HashServiceInterface {

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * HashService constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user service.
   */
  public function __construct(StateInterface $state, AccountProxyInterface $current_user) {
    $this->state = $state;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function getHash() {
    $rids = $this->currentUser->getRoles();
    sort($rids);

    // Prevent user 1 accounts without the administrator role leaking
    // information via DRUPAL_AUTHENTICATED_RID.
    if ($this->currentUser->id() === 1) {
      $rids[] = 'fac_#_dummy_role';
    }

    $hash = Crypt::hmacBase64('fac-' . implode('|', $rids), $this->getKey());

    return $hash;
  }

  /**
   * {@inheritdoc}
   */
  public function isValidHash($hash) {
    $result = FALSE;
    if ($hash == $this->getHash()) {
      $result = TRUE;
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getKey($renewal = FALSE) {
    if (!$key = $this->state->get('fac_key') || $renewal) {
      $key = Crypt::randomBytesBase64();
      $this->state->set('fac_key', $key);
      $this->state->set('fac_key_timestamp', (int) $_SERVER['REQUEST_TIME']);
    }

    return $key;
  }

}
