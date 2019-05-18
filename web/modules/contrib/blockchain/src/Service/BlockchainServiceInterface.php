<?php

namespace Drupal\blockchain\Service;

/**
 * Interface BlockchainServiceInterface.
 *
 * @package Drupal\blockchain\Service
 */
interface BlockchainServiceInterface {

  /**
   * Getter for settings service.
   *
   * @return BlockchainConfigServiceInterface
   *   Config service.
   */
  public function getConfigService();

  /**
   * Getter for blockchain storage service.
   *
   * @return BlockchainStorageServiceInterface
   *   Service object.
   */
  public function getStorageService();

  /**
   * Blockchain queue service.
   *
   * @return BlockchainQueueServiceInterface
   *   Service object.
   */
  public function getQueueService();

  /**
   * Getter for API service.
   *
   * @return BlockchainApiServiceInterface
   *   Service object.
   */
  public function getApiService();

  /**
   * Blockchain data manager.
   *
   * @return \Drupal\blockchain\Plugin\BlockchainDataManager
   *   Service.
   */
  public function getDataManager();

  /**
   * Static call for service.
   *
   * @return BlockchainServiceInterface
   *   Service instance.
   */
  public static function instance();

  /**
   * Getter for Blockchain Node service.
   *
   * @return BlockchainNodeServiceInterface
   *   Service object.
   */
  public function getNodeService();

  /**
   * Getter for validator service.
   *
   * @return BlockchainValidatorServiceInterface
   *   Service object.
   */
  public function getValidatorService();

  /**
   * Getter for miner service.
   *
   * @return BlockchainMinerServiceInterface
   *   Service object.
   */
  public function getMinerService();

  /**
   * Getter for locker service.
   *
   * @return BlockchainLockerServiceInterface
   *   Service object.
   */
  public function getLockerService();

  /**
   * Manager for auth plugins.
   *
   * @return \Drupal\blockchain\Plugin\BlockchainAuthManager
   *   Manager object.
   */
  public function getAuthManager();

  /**
   * Temporary storage.
   *
   * @return BlockchainTempStoreServiceInterface
   *   Service object.
   */
  public function getTempStoreService();

  /**
   * Getter for Blockchain hash service.
   *
   * @return BlockchainHashServiceInterface
   *   Blockchain hash service.
   */
  public function getHashService();

  /**
   * Getter for collision handler service.
   *
   * @return BlockchainCollisionHandlerServiceInterface
   *   Collision handler service.
   */
  public function getCollisionHandler();

}
