<?php

namespace Drupal\blockchain\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Blockchain config entities.
 */
interface BlockchainConfigInterface extends ConfigEntityInterface {

  const ENTITY_TYPE = 'blockchain_config';
  const TYPE_SINGLE = 'single';
  const TYPE_MULTIPLE = 'multiple';
  const POOL_MANAGEMENT_MANUAL = 'manual';
  const POOL_MANAGEMENT_CRON = 'cron';
  const ANNOUNCE_MANAGEMENT_CRON = 'cron';
  const ANNOUNCE_MANAGEMENT_IMMEDIATE = 'immediate';
  const INTERVAL_DEFAULT = 600;
  const POW_POSITION_START = 'start';
  const POW_POSITION_END = 'end';
  const POW_EXPRESSION = '00';
  const DATA_HANDLER = 'simple';
  const FILTER_TYPE_BLACKLIST = 'blacklist';
  const FILTER_TYPE_WHITELIST = 'whitelist';
  const TIMEOUT_POOL = 600;
  const PULL_SIZE_ANNOUNCE = 5;
  const SEARCH_INTERVAL_ANNOUNCE = 2;

  /**
   * Generator for UUID.
   *
   * @return string
   *   Value.
   */
  public function getBlockchainId();

  /**
   * Property setter.
   *
   * @param string $blockchainId
   *   Value.
   *
   * @return $this
   *   Chaining.
   */
  public function setBlockchainId($blockchainId);

  /**
   * Property getter.
   *
   * @return string
   *   Value.
   */
  public function getNodeId();

  /**
   * Property setter.
   *
   * @param string $nodeId
   *   Value.
   *
   * @return BlockchainConfigInterface
   *   Chaining.
   */
  public function setNodeId($nodeId);

  /**
   * Property getter.
   *
   * @return string
   *   Value.
   */
  public function getType();

  /**
   * Property setter.
   *
   * @param string $type
   *   Value.
   *
   * @return $this
   *   Chaining.
   */
  public function setType($type);

  /**
   * Property getter.
   *
   * @return string
   *   Value.
   */
  public function getAuth();

  /**
   * Property setter.
   *
   * @param string $authId
   *   Value.
   *
   * @return $this
   *   Chaining.
   */
  public function setAuth($authId);

  /**
   * Property getter.
   *
   * @return string
   *   Value.
   */
  public function getFilterType();

  /**
   * Property setter.
   *
   * @param string $filterType
   *   Value.
   *
   * @return $this
   *   Chaining.
   */
  public function setFilterType($filterType);

  /**
   * Property getter.
   *
   * @return string
   *   Value.
   */
  public function getFilterList();

  /**
   * Property setter.
   *
   * @param string $filterList
   *   Value.
   *
   * @return $this
   *   Chaining.
   */
  public function setFilterList($filterList);

  /**
   * Property getter.
   *
   * @return string
   *   Value.
   */
  public function getPoolManagement();

  /**
   * Property setter.
   *
   * @param string $poolManagement
   *   Value.
   *
   * @return $this
   *   Chaining.
   */
  public function setPoolManagement($poolManagement);

  /**
   * Property getter.
   *
   * @return string
   *   Value.
   */
  public function getAnnounceManagement();

  /**
   * Property setter.
   *
   * @param string $announceManagement
   *   Value.
   *
   * @return $this
   *   Chaining.
   */
  public function setAnnounceManagement($announceManagement);

  /**
   * Property getter.
   *
   * @return string|int
   *   Value.
   */
  public function getIntervalPool();

  /**
   * Property setter.
   *
   * @param string|int $intervalPool
   *   Value.
   *
   * @return $this
   *   Chaining.
   */
  public function setIntervalPool($intervalPool);

  /**
   * Property getter.
   *
   * @return string|int
   *   Value.
   */
  public function getTimeoutPool();

  /**
   * Property setter.
   *
   * @param string|int $timeoutPool
   *   Value.
   *
   * @return $this
   *   Chaining.
   */
  public function setTimeoutPool($timeoutPool);

  /**
   * Property getter.
   *
   * @return string|int
   *   Value.
   */
  public function getIntervalAnnounce();

  /**
   * Property setter.
   *
   * @param string|int $intervalAnnounce
   *   Value.
   *
   * @return $this
   *   Chaining.
   */
  public function setIntervalAnnounce($intervalAnnounce);

  /**
   * Property getter.
   *
   * @return string
   *   Value.
   */
  public function getPowPosition();

  /**
   * Property setter.
   *
   * @param string $powPosition
   *   Value.
   *
   * @return $this
   *   Chaining.
   */
  public function setPowPosition($powPosition);

  /**
   * Property getter.
   *
   * @return string
   *   Value.
   */
  public function getPowExpression();

  /**
   * Property setter.
   *
   * @param string $powExpression
   *   Value.
   *
   * @return $this
   *   Chaining.
   */
  public function setPowExpression($powExpression);

  /**
   * Property getter.
   *
   * @return string
   *   Value.
   */
  public function getDataHandler();

  /**
   * Property setter.
   *
   * @param string $dataHandler
   *   Value.
   *
   * @return $this
   *   Chaining.
   */
  public function setDataHandler($dataHandler);

  /**
   * Property getter.
   *
   * @return string
   *   Value.
   */
  public function getAllowNotSecure();

  /**
   * Property setter.
   *
   * @param bool $allowNotSecure
   *   Value.
   *
   * @return $this
   *   Chaining.
   */
  public function setAllowNotSecure($allowNotSecure);

  /**
   * Property getter.
   *
   * @return string
   *   Value.
   */
  public function getId();

  /**
   * Property setter.
   *
   * @param string $id
   *   Value.
   *
   * @return $this
   *   Chaining.
   */
  public function setId($id);

  /**
   * Property getter.
   *
   * @return string
   *   Value.
   */
  public function getLabel();

  /**
   * Property setter.
   *
   * @param string $label
   *   Value.
   *
   * @return $this
   *   Chaining.
   */
  public function setLabel($label);

  /**
   * Property getter.
   *
   * @return string|int
   *   Value.
   */
  public function getPullSizeAnnounce();

  /**
   * Property setter.
   *
   * @param string|int $pullSizeAnnounce
   *   Value.
   *
   * @return $this
   *   Chaining.
   */
  public function setPullSizeAnnounce($pullSizeAnnounce);

  /**
   * Property getter.
   *
   * @return string|int
   *   Value.
   */
  public function getSearchIntervalAnnounce();

  /**
   * Property setter.
   *
   * @param string|int $searchIntervalAnnounce
   *   Value.
   *
   * @return $this
   *   Chaining.
   */
  public function setSearchIntervalAnnounce($searchIntervalAnnounce);

  /**
   * Base method definition override.
   *
   * @return int
   *   Execution result
   */
  public function save();

  /**
   * Setter for blockchain nodes filter list.
   *
   * @param array $blockchainFilterList
   *   Given value.
   *
   * @return $this
   *   Chaining.
   */
  public function setBlockchainFilterListAsArray(array $blockchainFilterList);

  /**
   * Getter for blockchain nodes filter list.
   *
   * @return string[]
   *   Array of values.
   */
  public function getBlockchainFilterListAsArray();

}
