<?php

namespace Drupal\blockchain\Service;

use Drupal\blockchain\Entity\BlockchainBlock;
use Drupal\blockchain\Entity\BlockchainBlockInterface;
use Drupal\blockchain\Plugin\BlockchainDataInterface;
use Drupal\blockchain\Plugin\BlockchainDataManager;
use Drupal\Component\Utility\Random;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Class BlockchainStorageService.
 *
 * @package Drupal\blockchain\Service
 */
class BlockchainStorageService implements BlockchainStorageServiceInterface {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Blockchain config service.
   *
   * @var BlockchainConfigServiceInterface
   */
  protected $configService;

  /**
   * Blockchain data manager.
   *
   * @var \Drupal\blockchain\Plugin\BlockchainDataManager
   */
  protected $blockchainDataManager;

  /**
   * Blockchain validator.
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
   * Database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Blockchain hash service.
   *
   * @var BlockchainHashServiceInterface
   */
  protected $blockchainHashService;

  /**
   * BlockchainStorageService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Given service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   Logger factory.
   * @param BlockchainConfigServiceInterface $blockchainSettingsService
   *   Blockchain config service.
   * @param \Drupal\blockchain\Plugin\BlockchainDataManager $blockchainDataManager
   *   Blockchain data manager.
   * @param BlockchainValidatorServiceInterface $blockchainValidatorService
   *   Blockchain validator.
   * @param BlockchainMinerServiceInterface $blockchainMinerService
   *   Blockchain miner service.
   * @param \Drupal\Core\Database\Connection $database
   *   Database.
   * @param BlockchainHashServiceInterface $blockchainHashService
   *   Hash service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager,
                              LoggerChannelFactoryInterface $loggerFactory,
                              BlockchainConfigServiceInterface $blockchainSettingsService,
                              BlockchainDataManager $blockchainDataManager,
                              BlockchainValidatorServiceInterface $blockchainValidatorService,
                              BlockchainMinerServiceInterface $blockchainMinerService,
                              Connection $database,
                              BlockchainHashServiceInterface $blockchainHashService) {

    $this->entityTypeManager = $entityTypeManager;
    $this->loggerFactory = $loggerFactory;
    $this->configService = $blockchainSettingsService;
    $this->blockchainDataManager = $blockchainDataManager;
    $this->blockchainValidatorService = $blockchainValidatorService;
    $this->blockchainMinerService = $blockchainMinerService;
    $this->database = $database;
    $this->blockchainHashService = $blockchainHashService;
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

    try {
      $type = $this->configService->getCurrentConfig()->id();

      return $this->entityTypeManager->getStorage($type);
    }
    catch (\Exception $e) {
      $this->getLogger()
        ->error($e->getMessage() . $e->getTraceAsString());

      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getBlockCount() {

    return $this->getBlockStorage()
      ->getQuery()
      ->accessCheck(FALSE)
      ->count()
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getLastBlock() {

    $blockId = $this->getBlockStorage()
      ->getQuery()
      ->accessCheck(FALSE)
      ->sort('timestamp', 'DESC')
      ->sort('id', 'DESC')
      ->range(0, 1)
      ->execute();
    if ($blockId) {

      return $this->getBlockStorage()->load(current($blockId));
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
  public function getGenericBlock() {

    $block = $this->getRandomBlock();

    return $block;
  }

  /**
   * {@inheritdoc}
   */
  public function getRandomBlock($previousHash = NULL) {

    $rand = new Random();
    if (!$previousHash) {
      $previousHash = $this->blockchainHashService->hash($rand->string());
    }
    $block = $this->getBlockStorage()->create([]);
    if ($block instanceof BlockchainBlockInterface) {
      $block->setPreviousHash($previousHash);
      $block->setTimestamp(time());
      $block->setAuthor($this->configService->getCurrentConfig()->getNodeId());
      $block->setData('raw::' . $rand->string(mt_rand(7, 20)));
      $this->blockchainMinerService->mineBlock($block);

      return $block;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getBlockDataHandler($data = NULL) {

    $pluginId = $this->configService->getCurrentConfig()->getDataHandler();
    if ($data) {
      if ($extractedId = $this->blockchainDataManager->extractPluginId($data)) {
        $pluginId = $extractedId;
      }
    }
    try {

      return $this->blockchainDataManager->createInstance($pluginId, [
        BlockchainDataInterface::DATA_KEY => $data,
      ]);
    }
    catch (\Exception $e) {
      $this->getLogger()
        ->error($e->getMessage() . $e->getTraceAsString());

      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(BlockchainBlockInterface $block) {

    try {
      return $this->getBlockStorage()->save($block);
    }
    catch (\Exception $e) {
      $this->getLogger()
        ->error($e->getMessage() . $e->getTraceAsString());

      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function loadByTimestampAndHash($timestamp, $previousHash) {

    $blockId = $this->getBlockStorage()
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('timestamp', $timestamp)
      ->condition('previous_hash', $previousHash)
      ->execute();
    if ($blockId) {
      return $this->getBlockStorage()->load(current($blockId));
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function existsByTimestampAndHash($timestamp, $previousHash) {

    return (bool) $this->loadByTimestampAndHash($timestamp, $previousHash);
  }

  /**
   * {@inheritdoc}
   */
  public function getBlocksCountFrom(BlockchainBlockInterface $block) {

    return $this->getBlockStorage()
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('timestamp', $block->getTimestamp(), '>=')
      ->condition('id', $block->id(), '>')
      ->sort('id', $block->id(), '>')
      ->count()
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getBlocksFrom(BlockchainBlockInterface $block, $count, $asArray = TRUE) {

    $results = [];
    $blockIds = $this->getBlockStorage()
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('timestamp', $block->getTimestamp(), '>=')
      ->condition('id', $block->id(), '>')
      ->sort('timestamp')
      ->sort('id')
      ->range(0, $count)
      ->execute();
    foreach ($blockIds as $blockId) {
      if ($asArray) {
        $results[] = $this->getBlockStorage()->load($blockId)->toArray();
      }
      else {
        $results[] = $this->getBlockStorage()->load($blockId);
      }
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function createFromArray(array $values) {

    $block = BlockchainBlock::create([]);
    foreach ($values as $key => $value) {
      if (isset($block->$key)) {
        $block->set($key, $value);
      }
    }

    return $block;
  }

  /**
   * {@inheritdoc}
   */
  public function getBlocks($offset = NULL, $limit = NULL, $asArray = FALSE) {

    $blockIds = $this->getBlockStorage()
      ->getQuery()
      ->accessCheck(FALSE)
      ->range($offset, $limit)
      ->execute();
    /* @var \Drupal\blockchain\Entity\BlockchainBlockInterface[] $blocks*/
    $blocks = $this->getBlockStorage()->loadMultiple($blockIds);
    if ($asArray) {
      foreach ($blocks as &$block) {
        $block = $block->toArray();
      }
    }

    return $blocks;
  }

  /**
   * {@inheritdoc}
   */
  public function checkBlocks($offset = NULL, $limit = NULL) {

    $blocks = $this->getBlocks($offset, $limit);

    return $this->blockchainValidatorService->validateBlocks($blocks);
  }

  /**
   * {@inheritdoc}
   */
  public function getFirstBlock() {

    $blockId = $this->getBlockStorage()
      ->getQuery()
      ->accessCheck(FALSE)
      ->sort('timestamp')
      ->sort('id')
      ->range(0, 1)
      ->execute();
    if ($blockId) {
      return $this->getBlockStorage()->load(current($blockId));
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll() {

    $type = $this->configService->getCurrentConfig()->id();
    $this->database->delete($type)->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function pop() {

    if ($lastBlock = $this->getLastBlock()) {
      $lastBlock->delete();

      return $lastBlock;
    }

    return NULL;
  }

}
