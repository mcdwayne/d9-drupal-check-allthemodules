<?php

namespace Drupal\blockchain\Service;

use Drupal\Core\Lock\LockBackendInterface;

/**
 * Class BlockchainLockerService.
 *
 * @package Drupal\blockchain\Service
 */
class BlockchainLockerService implements BlockchainLockerServiceInterface {

  /**
   * Locker.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lockBackend;

  /**
   * Blockchain config.
   *
   * @var BlockchainConfigServiceInterface
   */
  protected $blockchainConfigService;

  /**
   * BlockchainLockerService constructor.
   *
   * @param \Drupal\Core\Lock\LockBackendInterface $lockBackend
   *   Locker.
   * @param BlockchainConfigServiceInterface $blockchainConfigService
   *   Blockchain config.
   */
  public function __construct(LockBackendInterface $lockBackend,
                              BlockchainConfigServiceInterface $blockchainConfigService) {

    $this->lockBackend = $lockBackend;
    $this->blockchainConfigService = $blockchainConfigService;
  }

  /**
   * {@inheritdoc}
   */
  public function lock($lockName) {

    return $this->lockBackend->acquire($lockName);
  }

  /**
   * {@inheritdoc}
   */
  public function release($lockName) {

    $this->lockBackend->release($lockName);
  }

  /**
   * {@inheritdoc}
   */
  public function wait($lockName, $timeout) {

    return $this->lockBackend->wait($lockName, $timeout);
  }

  /**
   * {@inheritdoc}
   */
  public function lockAnnounce() {

    return $this->lock(static::ANNOUNCE . $this->blockchainConfigService->getCurrentConfig()->id());
  }

  /**
   * {@inheritdoc}
   */
  public function releaseAnnounce() {

    $this->lockBackend->release(static::ANNOUNCE . $this->blockchainConfigService->getCurrentConfig()->id());
  }

  /**
   * {@inheritdoc}
   */
  public function waitAnnounce($timeout) {

    return $this->wait(static::ANNOUNCE . $this->blockchainConfigService->getCurrentConfig()->id(), $timeout);
  }

  /**
   * {@inheritdoc}
   */
  public function lockMining() {

    return $this->lock(static::MINING . $this->blockchainConfigService->getCurrentConfig()->id());
  }

  /**
   * {@inheritdoc}
   */
  public function releaseMining() {

    $this->lockBackend->release(static::MINING . $this->blockchainConfigService->getCurrentConfig()->id());
  }

  /**
   * {@inheritdoc}
   */
  public function waitMining($timeout) {

    return $this->wait(static::MINING . $this->blockchainConfigService->getCurrentConfig()->id(), $timeout);
  }

}
