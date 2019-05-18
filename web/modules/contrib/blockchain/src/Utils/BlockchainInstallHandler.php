<?php

namespace Drupal\blockchain\Utils;

use Drupal\blockchain\Service\BlockchainServiceInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BlockchainInstallHandler.
 *
 * @package Drupal\blockchain\Utils
 */
class BlockchainInstallHandler implements ContainerInjectionInterface {

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
   * BlockchainInstallHandler constructor.
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
   * Implements hook_install().
   */
  public function hookInstall() {

    $this->blockchainService
      ->getConfigService()
      ->discoverBlockchainConfigs();
  }

}
