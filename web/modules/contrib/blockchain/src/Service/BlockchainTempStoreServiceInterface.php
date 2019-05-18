<?php

namespace Drupal\blockchain\Service;

use Drupal\blockchain\Entity\BlockchainBlockInterface;

/**
 * Interface BlockchainTempStoreServiceInterface.
 *
 * @package Drupal\blockchain\Service
 */
interface BlockchainTempStoreServiceInterface {

  const LOGGER_CHANNEL = 'blockchain.tempstore';
  const STORAGE_PREFIX = 'blockchain_tempstore_';
  const BLOCKS_KEY = 'blocks';

  /**
   * Getter for logger.
   *
   * @return \Drupal\Core\Logger\LoggerChannelInterface
   *   Logger.
   */
  public function getLogger();

  /**
   * Getter for blockchain block storage.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface|\Drupal\Core\TempStore\SharedTempStore|null
   *   Storage object.
   */
  public function getBlockStorage();

  /**
   * Getter for all blocks collection.
   *
   * @return array|BlockchainBlockInterface[]
   *   Blocks if any.
   */
  public function getAll();

  /**
   * Getter for blockchain blocks count.
   *
   * @return int
   *   Number of items.
   */
  public function getBlockCount();

  /**
   * Check if blockchain is empty.
   *
   * @return bool
   *   Test result.
   */
  public function anyBlock();

  /**
   * Getter for last block if any.
   *
   * @return \Drupal\blockchain\Entity\BlockchainBlockInterface|null
   *   Blockchain block if any.
   */
  public function getLastBlock();

  /**
   * Save handler.
   *
   * @param \Drupal\blockchain\Entity\BlockchainBlockInterface $block
   *   Given block.
   *
   * @return mixed
   *   Execution result.
   */
  public function save(BlockchainBlockInterface $block);

  /**
   * Checks existing blocks in blockchain.
   *
   * Note that first bloc is checked only by nonce.
   * So if firs is not generic, previous block should
   * be fetched and checked.
   *
   * @return bool
   *   Test result.
   */
  public function checkBlocks();

  /**
   * Getter for first block.
   *
   * @return \Drupal\blockchain\Entity\BlockchainBlockInterface|\Drupal\Core\Entity\EntityInterface|null
   *   Block if any.
   */
  public function getFirstBlock();

  /**
   * Deletes all records.
   */
  public function deleteAll();

  /**
   * Deletes last block.
   *
   * @return \Drupal\blockchain\Entity\BlockchainBlockInterface|null
   *   Block if any.
   */
  public function pop();

  /**
   * Deletes first block.
   *
   * @return \Drupal\blockchain\Entity\BlockchainBlockInterface|null
   *   Block if any.
   */
  public function shift();

}
