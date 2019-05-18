<?php

namespace Drupal\blockchain\Service;

use Drupal\blockchain\Utils\BlockchainResponseInterface;

/**
 * Class BlockchainCollisionHandlerService.
 *
 * @package Drupal\blockchain\Service
 */
class BlockchainCollisionHandlerService implements BlockchainCollisionHandlerServiceInterface {

  /**
   * Blockchain storage service.
   *
   * @var BlockchainStorageServiceInterface
   */
  protected $blockchainStorageService;

  /**
   * Blockchain Config Service.
   *
   * @var BlockchainConfigServiceInterface
   */
  protected $blockchainSettingsService;

  /**
   * Blockchain Api Service.
   *
   * @var BlockchainApiServiceInterface
   */
  protected $blockchainApiService;

  /**
   * Blockchain Validator Service.
   *
   * @var BlockchainValidatorServiceInterface
   */
  protected $blockchainValidatorService;

  /**
   * Blockchain Temp Store Service.
   *
   * @var BlockchainTempStoreServiceInterface
   */
  protected $blockchainTempStoreService;

  /**
   * Blockchain Queue Service.
   *
   * @var BlockchainQueueServiceInterface
   */
  protected $blockchainQueueService;

  /**
   * BlockchainCollisionHandlerService constructor.
   *
   * @param BlockchainStorageServiceInterface $blockchainStorageService
   *   Injected service.
   * @param BlockchainConfigServiceInterface $blockchainSettingsService
   *   Injected service.
   * @param BlockchainApiServiceInterface $blockchainApiService
   *   Injected service.
   * @param BlockchainValidatorServiceInterface $blockchainValidatorService
   *   Injected service.
   * @param BlockchainTempStoreServiceInterface $blockchainTempStoreService
   *   Injected service.
   * @param BlockchainQueueServiceInterface $blockchainQueueService
   *   Injected service.
   */
  public function __construct(BlockchainStorageServiceInterface $blockchainStorageService,
                              BlockchainConfigServiceInterface $blockchainSettingsService,
                              BlockchainApiServiceInterface $blockchainApiService,
                              BlockchainValidatorServiceInterface $blockchainValidatorService,
                              BlockchainTempStoreServiceInterface $blockchainTempStoreService,
                              BlockchainQueueServiceInterface $blockchainQueueService) {

    $this->blockchainStorageService = $blockchainStorageService;
    $this->blockchainSettingsService = $blockchainSettingsService;
    $this->blockchainApiService = $blockchainApiService;
    $this->blockchainValidatorService = $blockchainValidatorService;
    $this->blockchainTempStoreService = $blockchainTempStoreService;
    $this->blockchainQueueService = $blockchainQueueService;
  }

  /**
   * {@inheritdoc}
   */
  public function isPullGranted(BlockchainResponseInterface $response) {

    // Can PULL if blocks found and there are pending blocks.
    if ($response->hasExistsParam() && $response->getExistsParam()
      && $response->isCountParamValid()) {

      return TRUE;
    }
    // Can PULL if any blocks in storage and there are pending blocks.
    if (!$this->blockchainStorageService->anyBlock() &&
      $response->isCountParamValid()) {

      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function processNoConflict(BlockchainResponseInterface $fetchResponse, $endPoint) {
    $neededBlocks = $fetchResponse->getCountParam();
    $addedBlocks = 0;
    $fetchLimit = $this->blockchainSettingsService->getCurrentConfig()->getPullSizeAnnounce();
    while ($neededBlocks > $addedBlocks) {
      $lastBlock = $this->blockchainStorageService->getLastBlock();
      $result = $this->blockchainApiService
        ->executePull($endPoint, $fetchLimit, $lastBlock);
      if ($result->hasBlocksParam()) {
        foreach ($result->getBlocksParam() as $item) {
          $block = $this->blockchainStorageService->createFromArray($item);
          if ($this->blockchainValidatorService
            ->blockIsValid($block, $lastBlock)) {
            $block->save();
          }
          else {
            throw new \Exception('Not valid block detected while pull.');
          }
        }
      }
      $addedBlocks += $fetchLimit;
    }

    return $neededBlocks;
  }

  /**
   * {@inheritdoc}
   */
  public function processConflict(BlockchainResponseInterface $fetchResponse, $endPoint) {

    $blockCount = $this->blockchainStorageService->getBlockCount();
    // Ensure remote blockchain declares priority.
    if ($fetchResponse->getCountParam() > $blockCount) {
      // Check if we have generic same.
      $result = $this->blockchainApiService
        ->executeFetch($endPoint, $this->blockchainStorageService->getFirstBlock());
      // This means generic matches, else this is fully different blockchain,
      // thus we take no action, sync possible with empty storage in this case.
      if ($this->isPullGranted($result)) {
        // We see that generic matches, find last match block.
        $i = 0;
        $blockSearchInterval = $this->blockchainSettingsService->getCurrentConfig()->getSearchIntervalAnnounce();
        // Ensure step is not more than count itself.
        $blockSearchInterval = ($blockCount < $blockSearchInterval) ? $blockCount : $blockSearchInterval;
        do {
          $i += $blockSearchInterval;
          $offset = ($blockCount - $i) < 0 ? 0 : $blockCount - $i;
          $blockchainBlocks = $this->blockchainStorageService->getBlocks($offset, $blockSearchInterval);
          $result = $this->blockchainApiService
            ->executeFetch($endPoint, reset($blockchainBlocks));
        } while (!$this->isPullGranted($result));
        // At this point we have array, where first block is valid.
        // Lets find index of first not valid block.
        $validIndex = 0;
        for ($i = 1; $i < count($blockchainBlocks); $i++) {
          $result = $this->blockchainApiService
            ->executeFetch($endPoint, $blockchainBlocks[$i]);
          if ($this->isPullGranted($result)) {
            $validIndex = $i;
          }
          else {
            break;
          }
        }
        // We found that!!!
        $validBlock = $blockchainBlocks[$validIndex];
        // Make sure temp store is clear.
        $this->blockchainTempStoreService->deleteAll();
        // Add this block as 'generic' to tempStorage.
        $this->blockchainTempStoreService->save($validBlock);
        $neededBlocks = $result->getCountParam();
        $addedBlocks = 0;
        $fetchLimit = $this->blockchainSettingsService->getCurrentConfig()->getPullSizeAnnounce();
        while ($neededBlocks > $addedBlocks) {
          // Use tempStorage service here...
          $lastBlock = $this->blockchainTempStoreService->getLastBlock();
          $result = $this->blockchainApiService
            ->executePull($endPoint, $fetchLimit, $lastBlock);
          if ($result->hasBlocksParam()) {
            foreach ($result->getBlocksParam() as $item) {
              $block = $this->blockchainStorageService->createFromArray($item);
              if ($this->blockchainValidatorService
                ->blockIsValid($block, $lastBlock)) {
                $this->blockchainTempStoreService->save($block);
              }
              else {
                // Delete any blocks.
                $this->blockchainTempStoreService->deleteAll();
                throw new \Exception('Not valid block detected while pull.');
              }
            }
          }
          $addedBlocks += $fetchLimit;
        }
        // We should have valid blocks in cache collected.
        // Check again if we really must delete blocks from storage.
        $countToDelete = $this->blockchainStorageService->getBlocksCountFrom($validBlock);
        $countToAdd = $this->blockchainTempStoreService->getBlockCount();
        $nodeId = $this->blockchainSettingsService->getCurrentConfig()->getNodeId();
        if ($countToAdd > $countToDelete) {
          $lastBlock = $this->blockchainStorageService->getLastBlock();
          while ($lastBlock && !($validBlock->equals($lastBlock))) {
            if ($lastBlock->getAuthor() == $nodeId) {
              $this->blockchainQueueService
                ->addBlockItem($lastBlock->getData(), $lastBlock->getEntityTypeId());
            }
            $this->blockchainStorageService->pop();
            $lastBlock = $this->blockchainStorageService->getLastBlock();
          }
        }
        // Move from cache to db.
        // Shift first existing block.
        $this->blockchainTempStoreService->shift();
        // Move to DB.
        while ($blockchainBlock = $this->blockchainTempStoreService->shift()) {
          $this->blockchainStorageService->save($blockchainBlock);
        }
        // Clear temp store for sure.
        $this->blockchainTempStoreService->deleteAll();

        return $countToAdd;
      }
    }

    return 0;
  }

}
