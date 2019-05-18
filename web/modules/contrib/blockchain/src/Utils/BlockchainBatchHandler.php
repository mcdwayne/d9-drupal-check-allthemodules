<?php

namespace Drupal\blockchain\Utils;

use Drupal\blockchain\Service\BlockchainService;

/**
 * Class BlockchainBatchHelper.
 *
 * @package Drupal\blockchain\Utils
 */
class BlockchainBatchHandler {

  /**
   * Initializes and starts mining batch.
   *
   * @param string|\Drupal\Core\Url $redirect
   *   Redirect location.
   *
   * @return null|\Symfony\Component\HttpFoundation\RedirectResponse
   *   Return redirect.
   */
  public static function doMiningBatch($redirect = NULL) {

    static::set(static::getMiningBatchDefinition());

    return static ::process($redirect);
  }

  /**
   * Setup handler for batch.
   *
   * @param array $definition
   *   Definition for batch.
   */
  public static function set(array $definition) {

    batch_set($definition);
  }

  /**
   * Starts batch processing.
   *
   * @param string|\Drupal\Core\Url $redirect
   *   Redirect location.
   *
   * @return null|\Symfony\Component\HttpFoundation\RedirectResponse
   *   Return redirect.
   */
  public static function process($redirect = NULL) {

    return batch_process($redirect);
  }

  /**
   * Mining batch definition.
   *
   * @return array
   *   Definition for batch.
   */
  public static function getMiningBatchDefinition() {

    $blockchainService = BlockchainService::instance();
    $count = $blockchainService->getQueueService()->getBlockPool()->numberOfItems();
    $batch = [
      'title' => t('Mining block...'),
      'operations' => [],
      'finished' => static::class . '::finalizeMiningBatch',
    ];
    while ($count) {
      $batch['operations'][] = [static::class . '::processMiningBatch', []];
      $count--;
    }

    return $batch;
  }

  /**
   * Mining batch definition.
   *
   * @return array
   *   Definition for batch.
   */
  public static function getAnnounceBatchDefinition() {

    $blockchainService = BlockchainService::instance();
    $count = $blockchainService->getQueueService()->getAnnounceQueue()->numberOfItems();
    $batch = [
      'title' => t('Handling announces...'),
      'operations' => [],
      'finished' => static::class . '::finalizeAnnounceBatch',
    ];
    while ($count) {
      $batch['operations'][] = [static::class . '::processAnnounceBatch', []];
      $count--;
    }

    return $batch;
  }

  /**
   * Batch processor.
   *
   * @param array $context
   *   Batch context.
   */
  public static function processMiningBatch(array &$context) {

    $blockchainService = BlockchainService::instance();
    $results = $context['results'] ? $context['results'] : [];
    if ($blockchainService->getQueueService()->doMining(1)) {
      $results[] = 1;
    }
    $context['message'] = t('Mining is in progress...(@count)', [
      '@count' => count($results),
    ]);
    $context['results'] = $results;
  }

  /**
   * Batch processor.
   *
   * @param array $context
   *   Batch context.
   */
  public static function processAnnounceBatch(array &$context) {

    $blockchainService = BlockchainService::instance();
    $results = $context['results'] ? $context['results'] : [];
    if ($blockchainService->getQueueService()->doAnnounceHandling(1)) {
      $results[] = 1;
    };
    $context['message'] = t('Announce handling is in progress...(@count)', [
      '@count' => count($results),
    ]);
    $context['results'] = $results;
  }

  /**
   * Batch finalizer.
   *
   * {@inheritdoc}
   */
  public static function finalizeMiningBatch($success, $results, $operations) {

    if ($success) {
      $message = t('@count blocks processed.', [
        '@count' => count($results),
      ]);
    }
    else {
      $message = t('Finished with an error.');
    }
    \Drupal::messenger()->addStatus($message);
  }

  /**
   * Batch finalizer.
   *
   * {@inheritdoc}
   */
  public static function finalizeAnnounceBatch($success, $results, $operations) {

    if ($success) {
      $message = t('@count announces processed.', [
        '@count' => count($results),
      ]);
    }
    else {
      $message = t('Finished with an error.');
    }
    \Drupal::messenger()->addStatus($message);
  }

}
