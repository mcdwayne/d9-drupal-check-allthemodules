<?php

namespace Drupal\blockchain\Service;

use Drupal\blockchain\Entity\BlockchainBlockInterface;

/**
 * Interface BlockchainMinerServiceInterface.
 *
 * @package Drupal\blockchain\Service
 */
interface BlockchainMinerServiceInterface {

  /**
   * Mining procedure.
   *
   * @param string $miningString
   *   Given value.
   * @param int $deadline
   *   Deadline for mining.
   *
   * @return int
   *   Nonce.
   *
   * @throws SuspendQueueException
   */
  public function mine($miningString, $deadline = 0);

  /**
   * Block, with all values set except for nonce.
   *
   * @param \Drupal\blockchain\Entity\BlockchainBlockInterface $blockchainBlock
   *   Given blockchain block.
   * @param int $deadline
   *   Deadline for mining.
   *
   * @throws SuspendQueueException
   */
  public function mineBlock(BlockchainBlockInterface $blockchainBlock, $deadline = 0);

}
