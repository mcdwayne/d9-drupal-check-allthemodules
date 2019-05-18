<?php

namespace Drupal\blockchain\Service;

use Drupal\blockchain\Entity\BlockchainBlockInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\TempStore\SharedTempStoreFactory;

/**
 * Class BlockchainTempStoreService.
 *
 * @package Drupal\blockchain\Service
 */
class BlockchainTempStoreService implements BlockchainTempStoreServiceInterface {

  /**
   * Logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Shared temp store factory.
   *
   * @var \Drupal\Core\TempStore\SharedTempStoreFactory
   */
  protected $storeFactory;

  /**
   * Blockchain config service.
   *
   * @var BlockchainConfigServiceInterface
   */
  protected $blockchainConfigService;

  /**
   * Blockchain validator service.
   *
   * @var BlockchainValidatorServiceInterface
   */
  protected $blockchainValidatorService;

  /**
   * BlockchainStorageService constructor.
   *
   * @param \Drupal\Core\TempStore\SharedTempStoreFactory $storeFactory
   *   Store factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   Logger factory.
   * @param BlockchainConfigServiceInterface $blockchainConfigService
   *   Blockchain config.
   * @param BlockchainValidatorServiceInterface $blockchainValidatorService
   *   Validator service.
   */
  public function __construct(SharedTempStoreFactory $storeFactory,
                              LoggerChannelFactoryInterface $loggerFactory,
                              BlockchainConfigServiceInterface $blockchainConfigService,
                              BlockchainValidatorServiceInterface $blockchainValidatorService) {

    $this->loggerFactory = $loggerFactory;
    $this->storeFactory = $storeFactory;
    $this->blockchainConfigService = $blockchainConfigService;
    $this->blockchainValidatorService = $blockchainValidatorService;
  }

  /**
   * {@inheritdoc}
   */
  public function getLogger() {

    return $this->loggerFactory->get(static::LOGGER_CHANNEL);
  }

  /**
   * {@inheritdoc}
   */
  public function getBlockStorage() {

    $storageName = static::STORAGE_PREFIX . $this->blockchainConfigService->getCurrentConfig()->id();

    return $this->storeFactory->get($storageName);
  }

  /**
   * {@inheritdoc}
   */
  public function getAll() {

    $storage = $this->getBlockStorage();
    $keys = $storage->get(static::BLOCKS_KEY) ? $storage->get(static::BLOCKS_KEY) : [];
    $results = [];
    foreach ($keys as $key) {
      $results[] = $storage->get($key);
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function getBlockCount() {

    $storage = $this->getBlockStorage();
    $keys = $storage->get(static::BLOCKS_KEY) ? $storage->get(static::BLOCKS_KEY) : [];

    return count($keys);

  }

  /**
   * {@inheritdoc}
   */
  public function getLastBlock() {

    $storage = $this->getBlockStorage();
    $keys = $storage->get(static::BLOCKS_KEY) ? $storage->get(static::BLOCKS_KEY) : [];
    if ($keys) {
      return $storage->get(max($keys));
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function anyBlock() {

    return (bool) $this->getBlockCount();
  }

  /**
   * {@inheritdoc}
   */
  public function checkBlocks() {

    $storage = $this->getBlockStorage();
    $keys = $storage->get(static::BLOCKS_KEY) ? $storage->get(static::BLOCKS_KEY) : [];
    $previousBlock = NULL;
    foreach ($keys as $key) {
      $blockchainBlock = $storage->get($key);
      if (!$this->blockchainValidatorService->blockIsValid($blockchainBlock, $previousBlock)) {

        return FALSE;
      }
      $previousBlock = $blockchainBlock;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function save(BlockchainBlockInterface $blockchainBlock) {

    $storage = $this->getBlockStorage();
    $keys = $storage->get(static::BLOCKS_KEY) ? $storage->get(static::BLOCKS_KEY) : [];
    $id = (!$keys) ? 1 : max($keys) + 1;
    $blockchainBlock->set('id', $id);
    $storage->set($id, $blockchainBlock);
    $keys[] = $id;
    $storage->set(static::BLOCKS_KEY, $keys);
  }

  /**
   * {@inheritdoc}
   */
  public function pop() {

    $storage = $this->getBlockStorage();
    $keys = $storage->get(static::BLOCKS_KEY) ? $storage->get(static::BLOCKS_KEY) : [];
    if ($keys) {
      $id = max($keys);
      $block = $storage->get($id);
      $storage->delete($id);
      unset($keys[array_search($id, $keys)]);
      $storage->set(static::BLOCKS_KEY, $keys);

      return $block;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function shift() {

    $storage = $this->getBlockStorage();
    $keys = $storage->get(static::BLOCKS_KEY) ? $storage->get(static::BLOCKS_KEY) : [];
    if ($keys) {
      $id = min($keys);
      $block = $storage->get($id);
      $storage->delete($id);
      unset($keys[array_search($id, $keys)]);
      $storage->set(static::BLOCKS_KEY, $keys);

      return $block;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getFirstBlock() {

    $storage = $this->getBlockStorage();
    $keys = $storage->get(static::BLOCKS_KEY) ? $storage->get(static::BLOCKS_KEY) : [];
    if ($keys) {
      return $storage->get(min($keys));
    }

    return NULL;
  }

  /**
   * Deletes all records.
   */
  public function deleteAll() {

    $storage = $this->getBlockStorage();
    $keys = $storage->get(static::BLOCKS_KEY) ? $storage->get(static::BLOCKS_KEY) : [];
    if ($keys) {
      foreach ($keys as $key) {
        $storage->delete($key);
      }
    }
    $storage->delete(static::BLOCKS_KEY);
  }

}
