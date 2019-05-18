<?php

namespace Drupal\cache_consistent\Cache;

/**
 * Class CacheConsistentScrubber.
 *
 * @package Drupal\cache_consistent\Cache
 */
class CacheConsistentScrubber implements CacheConsistentScrubberInterface {

  /**
   * {@inheritdoc}
   */
  public function scrub($operations) {
    // No need to do any scrubbing with one or less operations in buffer.
    if (count($operations) <= 1) {
      return $operations;
    }

    $deleteAll = -1;
    $invalidateAll = -1;

    $inserted_deleted = [];
    $invalidated = [];

    foreach (array_reverse($operations, TRUE) as $idx => $operation) {
      /* @var \Gielfeldt\TransactionalPHP\Operation $operation */

      // Any operation preceding deleteAll/removeBin can be scrubbed.
      if ($idx < $deleteAll) {
        unset($operations[$idx]);
        continue;
      }
      switch ($operation->getMetadata('operation')) {
        // Record deleteAll/removeBin operations and skip to the next.
        case 'deleteAll':
        case 'removeBin':
          $deleteAll = $idx;
          break;

        // Record invalidateAll operations and skip to the next.
        case 'invalidateAll':
          $invalidateAll = $idx;
          break;

        // Invalidations before invalidateAll can be scrubbed.
        case 'invalidate':
          if ($idx < $invalidateAll) {
            unset($operations[$idx]);
          }
          else {
            $cid = $operation->getMetadata('cid');
            if (!empty($invalidated[$cid])) {
              unset($operations[$idx]);
            }
            else {
              $invalidated[$cid] = TRUE;
            }
          }
          break;

        case 'invalidateMultiple':
          if ($idx < $invalidateAll) {
            unset($operations[$idx]);
          }
          else {
            // We cannot break up multiple invalidates (yet), so we only record
            // the cids.
            $cids = $operation->getMetadata('cids');
            foreach ($cids as $cid) {
              $invalidated[$cid] = TRUE;
            }
          }
          break;

        case 'invalidateTags':
          if ($idx < $invalidateAll) {
            unset($operations[$idx]);
          }
          break;

        // Only keep last set/delete operation for a specific cid.
        case 'set':
        case 'delete':
          $cid = $operation->getMetadata('cid');
          if (!empty($inserted_deleted[$cid])) {
            unset($operations[$idx]);
          }
          else {
            $inserted_deleted[$cid] = TRUE;
          }
          break;

        // Only keep last set/delete operation for a specific cid.
        case 'setMultiple':
          // We cannot break up multiple inserts (yet), so we only record the
          // cids.
          $items = $operation->getMetadata('items');
          foreach (array_keys($items) as $cid) {
            $inserted_deleted[$cid] = TRUE;
          }
          break;

        case 'deleteMultiple':
          // We cannot break up multiple deletes (yet), so we only record the
          // cids.
          $cids = $operation->getMetadata('cids');
          foreach ($cids as $cid) {
            $inserted_deleted[$cid] = TRUE;
          }
          break;

        default:
          throw new \RuntimeException('Operation: ' . $operation->getMetadata('operation') . ' is unsupported by this scrubber.');

      }
    }
    return $operations;
  }

}
