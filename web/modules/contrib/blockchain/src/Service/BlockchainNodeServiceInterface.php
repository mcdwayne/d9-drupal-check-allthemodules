<?php

namespace Drupal\blockchain\Service;

use Drupal\blockchain\Entity\BlockchainNodeInterface;
use Drupal\blockchain\Utils\BlockchainRequestInterface;

/**
 * Interface BlockchainNodeServiceInterface.
 *
 * @package Drupal\blockchain\Service
 */
interface BlockchainNodeServiceInterface {

  /**
   * Getter for storage.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface|null
   *   Storage.
   */
  public function getStorage();

  /**
   * Getter for list of Blockchain nodes.
   *
   * @param int $offset
   *   Offset.
   * @param int $limit
   *   Limit.
   *
   * @return \Drupal\blockchain\Entity\BlockchainNodeInterface[]
   *   Array of entities.
   */
  public function getList($offset = NULL, $limit = NULL);

  /**
   * Count query.
   *
   * @return int
   *   Count of items.
   */
  public function getCount();

  /**
   * Getter for list of Blockchain nodes.
   *
   * @param string $id
   *   Given id.
   *
   * @return bool
   *   Test result.
   */
  public function exists($id);

  /**
   * Getter for list of Blockchain nodes.
   *
   * @param string $self
   *   Given id.
   * @param string $blockchainTypeId
   *   Blockchain type id.
   *
   * @return bool
   *   Test result.
   */
  public function existsBySelfAndType($self, $blockchainTypeId);

  /**
   * Getter for list of Blockchain nodes.
   *
   * @param string $id
   *   Given id.
   *
   * @return \Drupal\blockchain\Entity\BlockchainNodeInterface|null
   *   Entity if any.
   */
  public function load($id);

  /**
   * Loads node by self and type.
   *
   * @param string $self
   *   Given id.
   * @param string $blockchainTypeId
   *   Blockchain type id.
   *
   * @return \Drupal\blockchain\Entity\BlockchainNodeInterface|null
   *   Entity if any.
   */
  public function loadBySelfAndType($self, $blockchainTypeId);

  /**
   * Factory method.
   *
   * @param string $blockchainType
   *   Type of blockchain.
   * @param string $self
   *   Id of blockchain node.
   * @param string $addressSource
   *   Address source.
   * @param string $address
   *   Client ip/host.
   * @param null|string $ip
   *   Given ip if any.
   * @param string $port
   *   Client port.
   * @param null|bool $secure
   *   Secure flag.
   * @param string $label
   *   Can be same as label.
   * @param bool $save
   *   Flag defines saving action.
   *
   * @return \Drupal\blockchain\Entity\BlockchainNodeInterface|null
   *   New entity if created.
   */
  public function create($blockchainType, $self, $addressSource, $address, $ip = NULL, $port = NULL, $secure = NULL, $label = NULL, $save = TRUE);

  /**
   * Factory method.
   *
   * @param \Drupal\blockchain\Utils\BlockchainRequestInterface $request
   *   Request.
   * @param bool $save
   *   Flag defines saving action.
   *
   * @return \Drupal\blockchain\Entity\BlockchainNodeInterface|null
   *   New entity if created.
   */
  public function createFromRequest(BlockchainRequestInterface $request, $save = TRUE);

  /**
   * Delete handler.
   *
   * @param \Drupal\blockchain\Entity\BlockchainNodeInterface $blockchainNode
   *   Given entity.
   *
   * @return bool
   *   Execution result.
   */
  public function delete(BlockchainNodeInterface $blockchainNode);

  /**
   * Save handler.
   *
   * @param \Drupal\blockchain\Entity\BlockchainNodeInterface $blockchainNode
   *   Given entity.
   *
   * @return bool
   *   Execution result.
   */
  public function save(BlockchainNodeInterface $blockchainNode);

}
