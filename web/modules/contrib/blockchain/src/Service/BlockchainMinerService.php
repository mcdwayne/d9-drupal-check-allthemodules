<?php

namespace Drupal\blockchain\Service;

use Drupal\blockchain\Entity\BlockchainBlockInterface;
use Drupal\Core\Queue\SuspendQueueException;

/**
 * Class BlockchainMinerService.
 *
 * @package Drupal\blockchain\Service
 */
class BlockchainMinerService implements BlockchainMinerServiceInterface {

  /**
   * Validator service.
   *
   * @var BlockchainValidatorServiceInterface
   */
  protected $blockchainValidatorService;

  /**
   * Blockchain hash service.
   *
   * @var BlockchainHashServiceInterface
   */
  protected $blockchainHashService;

  /**
   * BlockchainMinerService constructor.
   *
   * @param BlockchainValidatorServiceInterface $blockchainValidatorService
   *   Injected service.
   * @param BlockchainHashServiceInterface $blockchainHashService
   *   Miner service.
   */
  public function __construct(BlockchainValidatorServiceInterface $blockchainValidatorService,
                              BlockchainHashServiceInterface $blockchainHashService) {

    $this->blockchainValidatorService = $blockchainValidatorService;
    $this->blockchainHashService = $blockchainHashService;
  }

  /**
   * {@inheritdoc}
   */
  public function mine($previousHash, $deadline = 0) {

    $nonce = 0;
    $result = $this->blockchainHashService->hash($previousHash . $nonce);
    while (!$this->blockchainValidatorService->hashIsValid($result)) {
      if ($deadline && $deadline > time()) {
        throw new SuspendQueueException('Block mining timed out');
      }
      $nonce++;
      $result = $this->blockchainHashService->hash($previousHash . $nonce);
    }

    return $nonce;
  }

  /**
   * {@inheritdoc}
   */
  public function mineBlock(BlockchainBlockInterface $blockchainBlock, $deadline = 0) {

    $nonce = $this->mine($blockchainBlock->getPreviousHash());
    $blockchainBlock->setNonce($nonce);
  }

}
