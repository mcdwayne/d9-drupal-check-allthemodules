<?php

namespace Drupal\blockchain\Service;

use Drupal\blockchain\Plugin\BlockchainAuthManager;
use Drupal\blockchain\Plugin\BlockchainDataManager;

/**
 * Class BlockchainService.
 *
 * @package Drupal\blockchain\Service
 */
class BlockchainService implements BlockchainServiceInterface {

  /**
   * BlockchainSettingsService.
   *
   * @var BlockchainConfigServiceInterface
   */
  protected $blockchainServiceSettings;

  /**
   * Blockchain data manager.
   *
   * @var \Drupal\blockchain\Plugin\BlockchainDataManager
   */
  protected $blockchainDataManager;

  /**
   * Blockchain storage service.
   *
   * @var BlockchainStorageServiceInterface
   */
  protected $blockchainStorageService;

  /**
   * Blockchain queue service.
   *
   * @var BlockchainQueueServiceInterface
   */
  protected $blockchainQueueService;

  /**
   * Blockchain API service.
   *
   * @var BlockchainApiServiceInterface
   */
  protected $blockchainApiService;

  /**
   * Blockchain Node service.
   *
   * @var BlockchainNodeServiceInterface
   */
  protected $blockchainNodeService;

  /**
   * Blockchain validator service.
   *
   * @var BlockchainValidatorServiceInterface
   */
  protected $blockchainValidatorService;

  /**
   * Blockchain miner service.
   *
   * @var BlockchainMinerServiceInterface
   */
  protected $blockchainMinerService;

  /**
   * Blockchain locker service.
   *
   * @var BlockchainLockerServiceInterface
   */
  protected $blockchainLockerService;

  /**
   * Auth manager.
   *
   * @var \Drupal\blockchain\Plugin\BlockchainAuthManager
   */
  protected $blockchainAuthManager;

  /**
   * Temporary storage.
   *
   * @var BlockchainTempStoreServiceInterface
   */
  protected $blockchainTempStoreService;

  /**
   * Blockchain hash service.
   *
   * @var BlockchainHashServiceInterface
   */
  protected $blockchainHashService;

  /**
   * Collision handler service.
   *
   * @var BlockchainCollisionHandlerServiceInterface
   */
  protected $blockchainCollisionHandlerService;

  /**
   * BlockchainService constructor.
   *
   * @param BlockchainConfigServiceInterface $blockchainSettingsService
   *   Given service.
   * @param BlockchainStorageServiceInterface $blockchainStorageService
   *   Given service.
   * @param \Drupal\blockchain\Plugin\BlockchainDataManager $blockchainDataManager
   *   Given blockchain data manager.
   * @param BlockchainQueueServiceInterface $blockchainQueueService
   *   Given queue service.
   * @param BlockchainApiServiceInterface $blockchainApiService
   *   Given Blockchain API service.
   * @param BlockchainNodeServiceInterface $blockchainNodeService
   *   Given Blockchain Node service.
   * @param BlockchainValidatorServiceInterface $blockchainValidatorService
   *   Given Blockchain Validate service.
   * @param BlockchainMinerServiceInterface $blockchainMinerService
   *   Given Blockchain miner service.
   * @param BlockchainLockerServiceInterface $blockchainLockerService
   *   Blockchain locker service.
   * @param \Drupal\blockchain\Plugin\BlockchainAuthManager $blockchainAuthManager
   *   Blockchain auth manager.
   * @param BlockchainTempStoreServiceInterface $blockchainTempStoreService
   *   Blockchain temporary storage.
   * @param BlockchainHashServiceInterface $blockchainHashService
   *   Blockchain hash service.
   * @param BlockchainCollisionHandlerServiceInterface $blockchainCollisionHandlerService
   *   Collision handler.
   */
  public function __construct(
    BlockchainConfigServiceInterface $blockchainSettingsService,
    BlockchainStorageServiceInterface $blockchainStorageService,
    BlockchainDataManager $blockchainDataManager,
    BlockchainQueueServiceInterface $blockchainQueueService,
    BlockchainApiServiceInterface $blockchainApiService,
    BlockchainNodeServiceInterface $blockchainNodeService,
    BlockchainValidatorServiceInterface $blockchainValidatorService,
    BlockchainMinerServiceInterface $blockchainMinerService,
    BlockchainLockerServiceInterface $blockchainLockerService,
    BlockchainAuthManager $blockchainAuthManager,
    BlockchainTempStoreServiceInterface $blockchainTempStoreService,
    BlockchainHashServiceInterface $blockchainHashService,
    BlockchainCollisionHandlerServiceInterface $blockchainCollisionHandlerService) {

    $this->blockchainServiceSettings = $blockchainSettingsService;
    $this->blockchainDataManager = $blockchainDataManager;
    $this->blockchainStorageService = $blockchainStorageService;
    $this->blockchainQueueService = $blockchainQueueService;
    $this->blockchainApiService = $blockchainApiService;
    $this->blockchainNodeService = $blockchainNodeService;
    $this->blockchainValidatorService = $blockchainValidatorService;
    $this->blockchainMinerService = $blockchainMinerService;
    $this->blockchainLockerService = $blockchainLockerService;
    $this->blockchainAuthManager = $blockchainAuthManager;
    $this->blockchainTempStoreService = $blockchainTempStoreService;
    $this->blockchainHashService = $blockchainHashService;
    $this->blockchainCollisionHandlerService = $blockchainCollisionHandlerService;
  }

  /**
   * {@inheritdoc}
   */
  public static function instance() {

    return \Drupal::service('blockchain.service');
  }

  /**
   * {@inheritdoc}
   */
  public function getStorageService() {
    return $this->blockchainStorageService;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigService() {

    return $this->blockchainServiceSettings;
  }

  /**
   * {@inheritdoc}
   */
  public function getApiService() {
    return $this->blockchainApiService;
  }

  /**
   * {@inheritdoc}
   */
  public function getQueueService() {

    return $this->blockchainQueueService;
  }

  /**
   * {@inheritdoc}
   */
  public function getDataManager() {
    return $this->blockchainDataManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getNodeService() {

    return $this->blockchainNodeService;
  }

  /**
   * {@inheritdoc}
   */
  public function getValidatorService() {

    return $this->blockchainValidatorService;
  }

  /**
   * {@inheritdoc}
   */
  public function getMinerService() {

    return $this->blockchainMinerService;
  }

  /**
   * {@inheritdoc}
   */
  public function getLockerService() {

    return $this->blockchainLockerService;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthManager() {

    return $this->blockchainAuthManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getTempStoreService() {

    return $this->blockchainTempStoreService;
  }

  /**
   * {@inheritdoc}
   */
  public function getHashService() {

    return $this->blockchainHashService;
  }

  /**
   * {@inheritdoc}
   */
  public function getCollisionHandler() {

    return $this->blockchainCollisionHandlerService;
  }

}
