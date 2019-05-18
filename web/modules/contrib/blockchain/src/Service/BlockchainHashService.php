<?php

namespace Drupal\blockchain\Service;

use Drupal\blockchain\Entity\BlockchainBlockInterface;

/**
 * Class BlockchainHashService.
 *
 * @package Drupal\blockchain\Service
 */
class BlockchainHashService implements BlockchainHashServiceInterface {

  /**
   * Basic hash function.
   *
   * @param string $string
   *   Given string.
   *
   * @return string
   *   Hash.
   */
  public function hash($string) {

    return hash('sha256', $string);
  }

  /**
   * Hashes blockchain block.
   *
   * @param \Drupal\blockchain\Entity\BlockchainBlockInterface $blockchainBlock
   *   Given block.
   *
   * @return string
   *   Hash.
   */
  public function hashBlock(BlockchainBlockInterface $blockchainBlock) {

    return $this->hash(
      $blockchainBlock->getAuthor() .
      $blockchainBlock->getPreviousHash() .
      $blockchainBlock->getData() .
      $blockchainBlock->getTimestamp() .
      $blockchainBlock->getNonce()
    );
  }

}
