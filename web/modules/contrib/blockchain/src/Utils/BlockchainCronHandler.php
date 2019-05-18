<?php

namespace Drupal\blockchain\Utils;

use Drupal\blockchain\Entity\BlockchainConfigInterface;
use Drupal\blockchain\Service\BlockchainConfigServiceInterface;
use Drupal\blockchain\Service\BlockchainServiceInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BlockchainCronHandler.
 *
 * @package Drupal\blockchain\Utils
 */
class BlockchainCronHandler implements ContainerInjectionInterface {

  const LOGGER_CHANNEL = 'blockchain.cron';

  /**
   * Blockchain service.
   *
   * @var \Drupal\blockchain\Service\BlockchainServiceInterface
   */
  protected $blockchainService;

  /**
   * Logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {

    return new static(
      $container->get('blockchain.service'),
      $container->get('logger.factory')
    );
  }

  /**
   * BlockchainCronHandler constructor.
   *
   * @param \Drupal\blockchain\Service\BlockchainServiceInterface $blockchainService
   *   Blockchain service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   Logger factory.
   */
  public function __construct(BlockchainServiceInterface $blockchainService,
                              LoggerChannelFactoryInterface $loggerChannelFactory) {

    $this->blockchainService = $blockchainService;
    $this->logger = $loggerChannelFactory->get(static::LOGGER_CHANNEL);
  }

  /**
   * Implements hook_cron().
   */
  public function hookCron() {

    $configService = $this->blockchainService->getConfigService();
    $blockchainConfigs = $configService->getAll();
    foreach ($blockchainConfigs as $blockchainConfig) {
      $configService->setCurrentConfig($blockchainConfig);
      $poolManagement = $configService->getCurrentConfig()->getPoolManagement();
      if ($poolManagement == BlockchainConfigInterface::POOL_MANAGEMENT_CRON) {
        $poolInterval = $configService->getCurrentConfig()->getIntervalPool();
        $lastCronRunMining = $configService->getLastCronRun(BlockchainConfigServiceInterface::CONTEXT_MINING);
        if (!$lastCronRunMining || ($poolInterval + $lastCronRunMining) >= time()) {
          $blocksCount = $this->blockchainService->getQueueService()->doMining();
          if ($blocksCount) {
            $this->logger->info('Mined @count blocks', [
              '@count' => $blocksCount,
            ]);
          }
          $configService->setLastCronRun(BlockchainConfigServiceInterface::CONTEXT_MINING, time());
        }
      }
      $announceManagement = $configService->getCurrentConfig()->getAnnounceManagement();
      if ($announceManagement == BlockchainConfigInterface::ANNOUNCE_MANAGEMENT_CRON) {
        $announceInterval = $configService->getCurrentConfig()->getIntervalAnnounce();
        $lastCronRunAnnounce = $configService->getLastCronRun(BlockchainConfigServiceInterface::CONTEXT_ANNOUNCE);
        if (!$lastCronRunAnnounce || ($announceInterval + $lastCronRunAnnounce) >= time()) {
          $announceCount = $this->blockchainService->getQueueService()->doAnnounceHandling();
          if ($announceCount) {
            $this->logger->info('Processed @count announces', [
              '@count' => $announceCount,
            ]);
          }
          $configService->setLastCronRun(BlockchainConfigServiceInterface::CONTEXT_ANNOUNCE, time());
        }
      }
    }
  }

}
