<?php

namespace Drupal\blockchain\Plugin\QueueWorker;

use Drupal\blockchain\Entity\BlockchainBlock;
use Drupal\blockchain\Plugin\BlockchainDataInterface;
use Drupal\blockchain\Service\BlockchainQueueServiceInterface;
use Drupal\blockchain\Service\BlockchainServiceInterface;
use Drupal\blockchain\Utils\BlockchainRequestInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Queue\SuspendQueueException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processes import.
 *
 * @QueueWorker(
 * id = "blockchain_pool",
 * title = @Translation("Blockchain pool worker."),
 * )
 */
class BlockchainMiner extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  const LOGGER_CHANNEL = 'blockchain_pool';

  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * Blockchain service.
   *
   * @var \Drupal\blockchain\Service\BlockchainServiceInterface
   */
  protected $blockchainService;

  /**
   * Constructs a ImporterQueue worker.
   *
   * @param array $configuration
   *   Configuration array.
   * @param string $plugin_id
   *   Plugin id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerFactory
   *   Logger factory.
   * @param \Drupal\blockchain\Service\BlockchainServiceInterface $blockchainService
   *   Blockchain service.
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              LoggerChannelFactory $loggerFactory,
                              BlockchainServiceInterface $blockchainService) {

    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->loggerFactory = $loggerFactory;
    $this->blockchainService = $blockchainService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container,
                                array $configuration,
                                $plugin_id,
                                $plugin_definition) {

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory'),
      $container->get('blockchain.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $blockData = property_exists($data, BlockchainDataInterface::DATA_KEY) ?
      $data->{BlockchainDataInterface::DATA_KEY} : NULL;
    $blockchainTypeId = property_exists($data, BlockchainQueueServiceInterface::BLOCKCHAIN_TYPE_ID) ?
      $data->{BlockchainQueueServiceInterface::BLOCKCHAIN_TYPE_ID} : NULL;
    $startTime = property_exists($data, BlockchainQueueServiceInterface::START_TIME) ?
      $data->{BlockchainQueueServiceInterface::START_TIME} : NULL;
    if (!$startTime) {
      throw new \Exception('Start time not set.');
    }
    if (!$blockchainTypeId) {
      throw new \Exception('Missing blockchain type.');
    }
    if (!$blockData) {
      throw new \Exception('Missing block data.');
    }
    if (!$this->blockchainService->getDataManager()->extractPluginId($blockData)) {
      throw new \Exception('Invalid data handler plugin id.');
    }
    if (!$this->blockchainService->getConfigService()->setCurrentConfig($blockchainTypeId)) {
      throw new \Exception('Invalid blockchain type.');
    }
    if (!$lastBlock = $this->blockchainService->getStorageService()->getLastBlock()) {
      throw new \Exception('Missing generic block.');
    }
    if ($this->blockchainService->getLockerService()->lockMining()) {
      $deadline = $startTime + $this->blockchainService->getConfigService()->getCurrentConfig()->getTimeoutPool();
      $block = BlockchainBlock::create();
      $block->setPreviousHash($lastBlock->toHash());
      $block->setData($blockData);
      $block->setAuthor($this->blockchainService
        ->getConfigService()->getCurrentConfig()->getNodeId());
      $block->setTimestamp(time());
      $this->blockchainService->getMinerService()->mineBlock($block, $deadline);
      $block->save();
      $this->blockchainService->getApiService()->executeAnnounce([
        BlockchainRequestInterface::PARAM_COUNT => $this->blockchainService->getStorageService()->getBlockCount(),
      ]);
    }
    else {
      throw new SuspendQueueException('Block mining locked');
    }
  }

}
