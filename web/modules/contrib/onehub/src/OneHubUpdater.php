<?php

namespace Drupal\onehub;

use Drupal\onehub\OneHubApi;
use Drupal\Component\Utility\NestedArray;

class OneHubUpdater extends OneHubApi {

  /**
   * Items to be used in the UI Batch.
   *
   * @var array
   */
  protected $batchItems = [];

  /**
   * Items to be used in the Queue Batch.
   *
   * @var array
   */
  protected $queueItems = [];

  /**
   * Utility function for updating OneHub Tables in the batch.
   *
   * @param bool $isBatch
   *   Are we setting a UI based batch or not.
   *
   * @return array
   */
  public function updateOneHub($isBatch = TRUE) {
    // Clear out our old data first
    $query = \Drupal::database()
      ->select('onehub', 'o')
      ->fields('o')
      ->condition('entity_id', '0')
      ->execute();

    // Go through and check each file.
    // This is way faster than deleting the whole table
    // And then repopulating it.  Almost 4xs faster.
    foreach ($query->fetchAll()as $result) {
      $path = '/workspaces/' . $result->workspace . '/files/' . $result->oid;
      $call = $this->callApi($path);

      // If not there then delete this.
      if (empty($call)) {
        $query = \Drupal::database()
          ->delete('onehub')
          ->condition('oid', $result->oid)
          ->execute();
      }
      // If the file was renamed or duplicated, remove the original entry.
      elseif (isset($call['file']['id'])
              && $call['file']['id'] != $result->oid) {
        $query = \Drupal::database()
          ->delete('onehub')
          ->condition('oid', $result->oid)
          ->execute();
      }
    }

    $workspaces = $this->listWorkspaces();

    $fs_call = [];
    foreach ($workspaces as $wid => $workspace) {
      $path = '/workspaces/' . $wid . '/folders';
      $f_call = $this->callApi($path);

      // Grab the id.
      $id = NestedArray::getValue($f_call, ['items', 0, 'folder', 'id']);

      // Call the API again to get the folders.
      $path = $path . '/' . $id;
      $fs_call[] = $this->callApi($path);
    }

    return $this->loadItems($fs_call, $isBatch, FALSE);
  }

  /**
   * Utility function to grab items in a OneHub Call.
   *
   * @param array $call
   *   The called array of items.
   * @param bool $isBatch
   *   Are we setting a UI based batch or not.
   * @param bool $isRecursive
   *   Are we recursively calling this function.
   *
   * @return array
   *   The list of items in an array keyed id:filename.
   */
  private function loadItems(array $call, $isBatch, $isRecursive) {
    if (!$isRecursive) {
      $final = count($call) - 1;
      foreach ($call as $key => $c) {
        $this->processItem($c, $isBatch);

        // Process the queue after the final item has processed.
        if ($key == $final) {
          return $this->loadQueues($isBatch);
        }
      }
    }
    else {
      $this->processItem($call, $isBatch);
    }
  }

  /**
   * Processes each workspace folder items.
   *
   * @param array $call
   *   The called array of items.
   * @param bool $isBatch
   *   Are we setting a UI based batch or not.
   */
  protected function processItem($call, $isBatch) {
    foreach ($call['items'] as $i) {
      foreach ($i as $item) {
        // If this item has children, then grab those.
        if (isset($item['children_count']) && $item['children_count'] > 0) {
          // Grabs all items in the folder.
          if ($isBatch) {
            $this->batchItems[] = $item;
          }
          elseif (!$isBatch) {
            $this->queueItems[] = $item;
          }
          // Setup the right batch operations.
          $path = '/workspaces/' . $item['workspace_id'] . '/folders/' . $item['id'];
          $folders = $this->callApi($path);
          $this->loadItems($folders, $isBatch, TRUE);
        }
        else {
          // Grabs the files in the final folder.
          if ($isBatch) {
            $this->batchItems[] = $item;
          }
          elseif (!$isBatch) {
            $this->queueItems[] = $item;
          }
        }
      }
    }
  }

  /**
   * Loads up the queues for the batch mechanisms.
   *
   * @param bool $isBatch
   *   Are we setting a UI based batch or not.
   */
  protected function loadQueues($isBatch) {
    // This is for the queue based batch,
    if (!empty($this->queueItems) && !$isBatch) {
      return $this->queueItems;
    }

    // For the regular batch.
    if (!empty($this->batchItems) && $isBatch) {
      foreach ($this->batchItems as $item) {
        $operations[] = ['Drupal\onehub\Batch\OneHubBatch::batchProcess', [$item]];
      }
    }

    // This is for the ui based batch.
    if (isset($operations) && $isBatch) {
      // Set the batch to win the stuff.
      $batch = array(
        'title' => t('Importing OneHub File Info...'),
        'operations' => $operations,
        'init_message' => t('Importing Files to process.'),
        'finished' => 'Drupal\onehub\Batch\OneHubBatch::batchFinished',
        'file' => drupal_get_path('module', 'onehub') . '/src/Batch/OneHubBatch.php'
      );

      // Engage.
      batch_set($batch);
    }
    else {
      drupal_set_message(t('No Messages to Process!'), 'warning', TRUE);
    }

    // Fail safe return.
    return [];
  }
}
