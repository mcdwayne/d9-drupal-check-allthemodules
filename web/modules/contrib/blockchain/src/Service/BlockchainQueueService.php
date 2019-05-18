<?php

namespace Drupal\blockchain\Service;

use Drupal\blockchain\Plugin\BlockchainDataInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\Core\Queue\SuspendQueueException;

/**
 * Class BlockchainQueueService.
 *
 * @package Drupal\blockchain\Service
 */
class BlockchainQueueService implements BlockchainQueueServiceInterface {

  /**
   * Queue factory service.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * Queue worker manager.
   *
   * @var \Drupal\Core\Queue\QueueWorkerManagerInterface
   */
  protected $queueWorkerManager;

  /**
   * Logger interface.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * BlockchainQueueService constructor.
   *
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   *   Queue factory.
   * @param \Drupal\Core\Queue\QueueWorkerManagerInterface $queueWorkerManager
   *   Queue worker manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   Logger factory.
   */
  public function __construct(
    QueueFactory $queueFactory,
    QueueWorkerManagerInterface $queueWorkerManager,
    LoggerChannelFactoryInterface $loggerChannelFactory) {

    $this->queueFactory = $queueFactory;
    $this->queueWorkerManager = $queueWorkerManager;
    $this->loggerFactory = $loggerChannelFactory;
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
  public function getBlockPool() {

    return $this->queueFactory->get(static::BLOCK_POOL_NAME);
  }

  /**
   * {@inheritdoc}
   */
  public function getMiner() {

    try {
      return $this->queueWorkerManager->createInstance(static::BLOCK_POOL_NAME);
    }
    catch (\Exception $exception) {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addBlockItem($blockData, $blockchainTypeId) {

    $item = (object) [
      BlockchainDataInterface::DATA_KEY => $blockData,
      static::BLOCKCHAIN_TYPE_ID => $blockchainTypeId,
    ];
    $this->getBlockPool()->createItem($item);
  }

  /**
   * {@inheritdoc}
   */
  public function doMining($limit = 0, $leaseTime = 600) {

    $count = 0;
    $start = time();
    while (!$limit || $count < $limit) {
      if ($item = $this->getBlockPool()->claimItem($leaseTime)) {
        try {
          $item->data->{static::START_TIME} = $start;
          $this->getMiner()->processItem($item->data);
          $this->getBlockPool()->deleteItem($item);
          $count++;
        }
        catch (SuspendQueueException $e) {
          $this->getBlockPool()->releaseItem($item);
          break;
        }
        catch (\Exception $e) {
          $this->getLogger()
            ->error($e->getMessage() . $e->getTraceAsString());
        }
      }
      else {
        break;
      }
    }

    return $count;
  }

  /**
   * {@inheritdoc}
   */
  public function getAnnounceQueue() {

    return $this->queueFactory->get(static::ANNOUNCE_QUEUE_NAME);
  }

  /**
   * {@inheritdoc}
   */
  public function getAnnounceHandler() {

    try {

      return $this->queueWorkerManager->createInstance(static::ANNOUNCE_QUEUE_NAME);
    }
    catch (\Exception $exception) {

      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addAnnounceItem($announceData, $blockchainTypeId) {

    $item = (object) [
      static::ANNOUNCE_QUEUE_ITEM => $announceData,
      static::BLOCKCHAIN_TYPE_ID => $blockchainTypeId,
    ];
    $this->getAnnounceQueue()->createItem($item);
  }

  /**
   * {@inheritdoc}
   */
  public function doAnnounceHandling($limit = 0, $leaseTime = 600) {

    $i = 0;
    while (!$limit || $i < $limit) {
      if ($item = $this->getAnnounceQueue()->claimItem($leaseTime)) {
        try {
          $this->getAnnounceHandler()->processItem($item->data);
          $this->getAnnounceQueue()->deleteItem($item);
          $i++;
        }
        catch (SuspendQueueException $e) {
          $this->getAnnounceQueue()->releaseItem($item);
          break;
        }
        catch (\Exception $e) {
          $this->getAnnounceQueue()->deleteItem($item);
          $this->getLogger()
            ->error($e->getMessage() . $e->getTraceAsString());
        }
      }
      else {
        break;
      }
    }

    return $i;
  }

}
