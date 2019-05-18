<?php

namespace Drupal\blockchain\Service;

use Drupal\blockchain\Entity\BlockchainConfigInterface;
use Drupal\blockchain\Plugin\BlockchainAuthManager;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;

/**
 * Class BlockchainConfigServiceInterface.
 *
 * @package Drupal\blockchain\Service
 */
class BlockchainConfigService implements BlockchainConfigServiceInterface {

  /**
   * Uuid service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuid;

  /**
   * State service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * ConfigFactory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Blockchain config context.
   *
   * @var \Drupal\blockchain\Entity\BlockchainConfigInterface
   */
  protected static $blockchainConfig;

  /**
   * Blockchain hash service.
   *
   * @var BlockchainHashServiceInterface
   */
  protected $blockchainHashService;

  /**
   * BlockchainConfigServiceInterface constructor.
   *
   * {@inheritdoc}
   */
  public function __construct(UuidInterface $uuid,
                              StateInterface $state,
                              ConfigFactoryInterface $configFactory,
                              EntityTypeManagerInterface $entityTypeManager,
                              BlockchainHashServiceInterface $blockchainHashService) {

    $this->uuid = $uuid;
    $this->state = $state;
    $this->configFactory = $configFactory;
    $this->entityTypeManager = $entityTypeManager;
    $this->blockchainHashService = $blockchainHashService;
  }

  /**
   * {@inheritdoc}
   */
  public function generateId() {

    return $this->uuid->generate();
  }

  /**
   * {@inheritdoc}
   */
  public function getGlobalConfig($editable = FALSE) {

    if ($editable) {
      return $this->configFactory->getEditable('blockchain.config');
    }

    return $this->configFactory->get('blockchain.config');
  }

  /**
   * {@inheritdoc}
   */
  public function getState() {

    return $this->state;
  }

  /**
   * {@inheritdoc}
   */
  public function setCurrentConfig($blockchainConfig) {

    if ($blockchainConfig instanceof BlockchainConfigInterface) {
      if (!$this->exists($blockchainConfig->id())) {
        static::$blockchainConfig = $blockchainConfig;

        return TRUE;
      }
    }
    elseif (is_string($blockchainConfig)) {
      if ($blockchainConfigEntity = $this->getStorage()->load($blockchainConfig)) {
        static::$blockchainConfig = $blockchainConfigEntity;

        return TRUE;
      }
      else {
        $blockchainEntityTypes = $this->getBlockchainEntityTypes();
        if (in_array($blockchainConfig, $blockchainEntityTypes)) {
          $blockchainConfigEntity = $this->getDefaultBlockchainConfig($blockchainConfig);
          if ($this->save($blockchainConfigEntity)) {
            static::$blockchainConfig = $blockchainConfigEntity;

            return TRUE;
          }
        }
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentConfig() {

    if (static::$blockchainConfig) {

      return static::$blockchainConfig;
    }

    throw new \Exception('Get not set blockchain config.');
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultBlockchainConfig($entityTypeId) {

    /** @var \Drupal\blockchain\Entity\BlockchainConfigInterface $blockchainConfig */
    $blockchainConfig = $this->getStorage()->create([]);
    $blockchainConfig->setId($entityTypeId);
    $blockchainConfig->setLabel($entityTypeId);
    $blockchainConfig->setBlockchainId($this->generateId());
    $blockchainConfig->setNodeId($this->generateId());
    $blockchainConfig->setType(BlockchainConfigInterface::TYPE_SINGLE);
    $blockchainConfig->setAuth(BlockchainAuthManager::DEFAULT_PLUGIN);
    $blockchainConfig->setAllowNotSecure(TRUE);
    $blockchainConfig->setAnnounceManagement(BlockchainConfigInterface::ANNOUNCE_MANAGEMENT_IMMEDIATE);
    $blockchainConfig->setPoolManagement(BlockchainConfigInterface::POOL_MANAGEMENT_MANUAL);
    $blockchainConfig->setDataHandler('raw');
    $blockchainConfig->setPowPosition(BlockchainConfigInterface::POW_POSITION_START);
    $blockchainConfig->setPowExpression('00');
    $blockchainConfig->setIntervalPool(BlockchainConfigInterface::INTERVAL_DEFAULT);
    $blockchainConfig->setIntervalAnnounce(BlockchainConfigInterface::INTERVAL_DEFAULT);
    $blockchainConfig->setFilterType(BlockchainConfigInterface::FILTER_TYPE_BLACKLIST);
    $blockchainConfig->setTimeoutPool(BlockchainConfigInterface::TIMEOUT_POOL);
    $blockchainConfig->setPullSizeAnnounce(BlockchainConfigInterface::PULL_SIZE_ANNOUNCE);
    $blockchainConfig->setSearchIntervalAnnounce(BlockchainConfigInterface::SEARCH_INTERVAL_ANNOUNCE);

    return $blockchainConfig;
  }

  /**
   * {@inheritdoc}
   */
  public function discoverBlockchainConfigs() {

    $count = 0;
    foreach ($this->getBlockchainEntityTypes() as $blockchainEntityType) {
      if (!$this->exists($blockchainEntityType)) {
        $blockchainConfig = $this->getDefaultBlockchainConfig($blockchainEntityType);
        if ($this->save($blockchainConfig)) {
          $count++;
        }
      }
    }

    return $count;
  }

  /**
   * {@inheritdoc}
   */
  public function getBlockchainEntityTypes() {

    $blockchainEntityTypes = [];
    foreach ($this->entityTypeManager->getDefinitions() as $definition) {
      if ($additional = $definition->get('additional')) {
        if (isset($additional['blockchain_entity']) && $additional['blockchain_entity']) {
          $blockchainEntityTypes[] = $definition->id();
        }
      }
    }

    return $blockchainEntityTypes;
  }

  /**
   * {@inheritdoc}
   */
  public function getStorage() {

    try {

      return $this->entityTypeManager
        ->getStorage(BlockchainConfigInterface::ENTITY_TYPE);
    }
    catch (\Exception $exception) {

      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function load($id) {

    return $this->getStorage()->load($id);
  }

  /**
   * {@inheritdoc}
   */
  public function save(BlockchainConfigInterface $blockchainConfig) {

    try {
      $this->getStorage()->save($blockchainConfig);

      return TRUE;
    }
    catch (\Exception $exception) {

      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function exists($blockchainConfigId) {

    return (bool) $this->getStorage()->load($blockchainConfigId);
  }

  /**
   * {@inheritdoc}
   */
  public function getAll() {

    return $this->getStorage()->loadMultiple();
  }

  /**
   * {@inheritdoc}
   */
  public function getList() {

    $list = [];
    foreach ($this->getAll() as $config) {
      $list[$config->id()] = $config->label();
    }

    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastCronRun($context) {

    if ($currentConfig = $this->getCurrentConfig()) {
      $suffix = $context . $currentConfig->id();

      return $this->getState()->get(static::LAST_CRON_RUN . $suffix, NULL);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setLastCronRun($context, $value) {

    if ($currentConfig = $this->getCurrentConfig()) {
      $suffix = $context . $currentConfig->id();
      $this->getState()->set(static::LAST_CRON_RUN . $suffix, $value);

      return TRUE;
    }

    return FALSE;
  }

}
