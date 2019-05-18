<?php

namespace Drupal\blockchain\Service;

use Drupal\blockchain\Entity\BlockchainBlockInterface;

/**
 * Interface BlockchainHashServiceInterface.
 *
 * @package Drupal\blockchain\Service
 */
interface BlockchainHashServiceInterface {

  /**
   * Basic hash function.
   *
   * @param string $string
   *   Given string.
   *
   * @return string
   *   Hash.
   */
  public function hash($string);

  /**
   * Hashes blockchain block.
   *
   * @param \Drupal\blockchain\Entity\BlockchainBlockInterface $blockchainBlock
   *   Given block.
   *
   * @return string
   *   Hash.
   */
  public function hashBlock(BlockchainBlockInterface $blockchainBlock);

}
