<?php

namespace Drupal\blockchain\Service;

/**
 * Interface BlockchainLockerServiceInterface.
 *
 * @package Drupal\blockchain\Service
 */
interface BlockchainLockerServiceInterface {

  const ANNOUNCE = 'blockchain_announce';
  const MINING = 'blockchain_mining';

  /**
   * Tries to lock against given name.
   *
   * @param string $lockName
   *   Lock name.
   *
   * @return bool
   *   Result.
   */
  public function lock($lockName);

  /**
   * Releases lock by name.
   *
   * @param string $lockName
   *   Lock name.
   */
  public function release($lockName);

  /**
   * Waits for lock to release given time.
   *
   * @param string $lockName
   *   Lock name.
   * @param int $timeout
   *   Timeout.
   *
   * @return bool
   *   Result.
   */
  public function wait($lockName, $timeout);

  /**
   * Tries to lock against given name.
   *
   * @return bool
   *   Result.
   */
  public function lockAnnounce();

  /**
   * Releases lock by name.
   */
  public function releaseAnnounce();

  /**
   * Waits for lock to release given time.
   *
   * @param int $timeout
   *   Timeout.
   *
   * @return bool
   *   Result.
   */
  public function waitAnnounce($timeout);

  /**
   * Tries to lock against given name.
   *
   * @return bool
   *   Result.
   */
  public function lockMining();

  /**
   * Releases lock by name.
   */
  public function releaseMining();

  /**
   * Waits for lock to release given time.
   *
   * @param int $timeout
   *   Timeout.
   *
   * @return bool
   *   Result.
   */
  public function waitMining($timeout);

}
