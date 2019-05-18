<?php

namespace Drupal\blockchain\Service;

use Drupal\blockchain\Entity\BlockchainConfigInterface;

/**
 * Interface BlockchainConfigServiceInterface.
 *
 * @package Drupal\blockchain\Service
 */
interface BlockchainConfigServiceInterface {

  const LAST_CRON_RUN = 'last_cron_run_';
  const CONTEXT_ANNOUNCE = 'announce_';
  const CONTEXT_MINING = 'mining_';

  /**
   * Getter for unique identifier.
   *
   * @return string
   *   Unique identifier.
   */
  public function generateId();

  /**
   * Getter for config.
   *
   * @param bool $editable
   *   Defines result.
   *
   * @return \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   *   Global config storage.
   */
  public function getGlobalConfig($editable = FALSE);

  /**
   * State storage.
   *
   * @return \Drupal\Core\State\StateInterface
   *   State storage.
   */
  public function getState();

  /**
   * Setter for blockchain config.
   *
   * After this action service is aware of blockchain type and
   * settings to use.
   *
   * @param \Drupal\blockchain\Entity\BlockchainConfigInterface|string $blockchainConfig
   *   Blockchain config of id.
   *
   * @return bool
   *   Execution result.
   */
  public function setCurrentConfig($blockchainConfig);

  /**
   * Getter for blockchain config.
   *
   * @return \Drupal\blockchain\Entity\BlockchainConfigInterface|null
   *   Returns config entity if any.
   */
  public function getCurrentConfig();

  /**
   * Creates config with default settings for entity id.
   *
   * @param string $entityTypeId
   *   Entity type id.
   *
   * @return \Drupal\blockchain\Entity\BlockchainConfigInterface|\Drupal\Core\Entity\EntityInterface
   *   Default config.
   */
  public function getDefaultBlockchainConfig($entityTypeId);

  /**
   * Save handler.
   *
   * @param \Drupal\blockchain\Entity\BlockchainConfigInterface $blockchainConfig
   *   Given entity.
   *
   * @return bool
   *   Execution result.
   */
  public function save(BlockchainConfigInterface $blockchainConfig);

  /**
   * Predicate defines if config exists.
   *
   * @param string $blockchainConfigId
   *   Given id.
   *
   * @return bool
   *   Test result.
   */
  public function exists($blockchainConfigId);

  /**
   * Helper to get blockchain entity types.
   *
   * @return array|string[]
   *   Array of entity type ids.
   */
  public function getBlockchainEntityTypes();

  /**
   * Handler to discover and save blockchain configs.
   *
   * @return int
   *   Count of discovered configs.
   */
  public function discoverBlockchainConfigs();

  /**
   * Handler to get list of blockchain configs.
   *
   * @return \Drupal\blockchain\Entity\BlockchainConfigInterface[]|array
   *   Array of entities if any.
   */
  public function getAll();

  /**
   * Handler to get list of blockchain configs.
   *
   * @return array|string[]
   *   List array.
   */
  public function getList();

  /**
   * Getter for config storage.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface|null
   *   Storage.
   */
  public function getStorage();

  /**
   * Shortcut to load entity.
   *
   * @param string $id
   *   Given id.
   *
   * @return \Drupal\blockchain\Entity\BlockchainConfigInterface
   *   Loaded entity.
   */
  public function load($id);

  /**
   * Getter for CRON last run in context of current config.
   *
   * @param string $context
   *   Context.
   *
   * @return int|null
   *   Value if any.
   */
  public function getLastCronRun($context);

  /**
   * Setter for last cron run.
   *
   * @param string $context
   *   Context.
   * @param int $value
   *   Integer value expected.
   *
   * @return bool
   *   Execution result.
   */
  public function setLastCronRun($context, $value);

}
