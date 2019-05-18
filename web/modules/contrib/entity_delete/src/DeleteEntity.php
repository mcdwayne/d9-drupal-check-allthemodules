<?php

namespace Drupal\entity_delete;

/**
 * Service for Deleting the entity.
 */
class DeleteEntity {

  /**
   * Function to delete all the entities.
   *
   * @param int $delete_ids
   *   Entity ids to be deleted.
   * @param int $count
   *   Current row in the batch process.
   * @param int $total_count
   *   Total count of ids in batch process.
   * @param string $entity
   *   Entity selected.
   * @param string $bundle
   *   Bundle selected.
   * @param string $context
   *   Context.
   */
  public static function deleteEntity($delete_ids, $count, $total_count, $entity, $bundle, &$context) {
    $context['message'] = t("Now processing :current_row of :highest_row", [':current_row' => $count, ":highest_row" => $total_count]);
    entity_delete_multiple($entity, $delete_ids);
    $context['results']['entity'] = $entity;
    $context['results']['bundle'] = $bundle;
    $context['results']['count'] = $total_count;
  }

  /**
   * Entity Delete Callback.
   *
   * @param string $success
   *   Return message on success.
   * @param int $results
   *   Giving the total count of contents deleted.
   * @param string $operations
   *   Delete operation.
   */
  public static function deleteEntityFinishedCallback($success, $results, $operations) {
    if ($success) {
      if ($results['bundle'] == 'all') {
        $message = t('Successfully deleted @num @entity(s).', ['@num' => $results['count'], '@entity' => $results['entity']]);
      }
      else {
        $message = t('Successfully deleted @num @entity(s) with @bundle.', [
          '@num' => $results['count'],
          '@entity' => $results['entity'],
          '@bundle' => $results['bundle'],
        ]);
      }
    }
    else {
      $message = t('Finished with an error.');
    }
    drupal_set_message($message);
  }

}
