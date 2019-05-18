<?php

namespace Drupal\blockchain\Plugin\QueueWorker;

use Drupal\blockchain\Service\BlockchainQueueServiceInterface;
use Drupal\blockchain\Service\BlockchainServiceInterface;
use Drupal\blockchain\Utils\BlockchainRequest;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Queue\SuspendQueueException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processes announce handling.
 *
 * @QueueWorker(
 * id = "announce_queue",
 * title = @Translation("Announce queue handler."),
 * )
 */
class AnnounceHandler extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  const LOGGER_CHANNEL = 'announce_handler';

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

    $announceData = property_exists($data, BlockchainQueueServiceInterface::ANNOUNCE_QUEUE_ITEM) ?
      $data->{BlockchainQueueServiceInterface::ANNOUNCE_QUEUE_ITEM} : NULL;
    if (!$announceData) {
      throw new \Exception('Missing announce data.');
    }
    if (!($blockchainRequest = BlockchainRequest::wakeup($announceData))) {
      throw new \Exception('Invalid announce queue data.');
    }
    $blockchainNode = $this->blockchainService->getNodeService()->loadBySelfAndType(
      $blockchainRequest->getSelfParam(), $blockchainRequest->getTypeParam());
    if (!($blockchainNode)) {
      throw new \Exception('Invalid announce request data.');
    }
    $endPoint = $blockchainNode->getEndPoint();
    if ($this->blockchainService->getLockerService()->lockAnnounce()) {
      try {
        $result = $this->blockchainService->getApiService()
          ->executeFetch($endPoint, $this->blockchainService->getStorageService()->getLastBlock());
        $collisionHandler = $this->blockchainService->getCollisionHandler();
        if ($collisionHandler->isPullGranted($result)) {
          $collisionHandler->processNoConflict($result, $endPoint);
        }
        elseif ($result->isCountParamValid()) {
          $collisionHandler->processConflict($result, $endPoint);
        }
      }
      finally {
        $this->blockchainService->getLockerService()->releaseAnnounce();
      }
    }
    else {
      throw new SuspendQueueException('Announce handling locked');
    }
  }

}
