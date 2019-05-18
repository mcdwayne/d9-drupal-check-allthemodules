<?php

namespace Drupal\client_config_care\Validator;

use Drupal\client_config_care\ConfigBlockerEntityStorage;
use Drupal\client_config_care\Exception\ExistingConfigBlockerException;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Logger\LoggerChannelInterface;

class NonblockingConfigValidator {

  /**
   * @var ConfigBlockerEntityStorage
   */
  private $configBlockerEntityStorage;

  /**
   * @var LoggerChannelInterface
   */
  private $logger;

  public function __construct(EntityTypeManager $entityTypeManager, LoggerChannelInterface $logger)
  {
    $this->configBlockerEntityStorage = $entityTypeManager->getStorage('config_blocker_entity');
    $this->logger = $logger;
  }

  /**
   * @throws ExistingConfigBlockerException
   */
  public function ensureNonblocking(string $configName): void {
    if ($this->configBlockerEntityStorage->isBlockerExisting($configName)) {
      $exception = new ExistingConfigBlockerException($configName);
      $this->logger->notice($exception->getNoticeMessage());
      throw $exception;
    }
  }

}
