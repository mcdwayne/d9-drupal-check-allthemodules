<?php

namespace Drupal\crm_core_user_sync;

/**
 * Methods for running the ConfigImporter in a batch.
 *
 * @package Drupal\crm_core_user_sync
 */
class UserSyncBatch {

  /**
   * Batch operation callback.
   */
  public static function progress(&$context) {
    $userStorage = \Drupal::entityTypeManager()->getStorage('user');

    if (empty($context['sandbox'])) {
      $max = $userStorage
        ->getQuery()
        ->condition('uid', 1, '>')
        ->count()
        ->execute();

      $context['sandbox']['max'] = $max;
      $context['sandbox']['progress'] = 0;
      $context['results']['synced'] = 0;
      $context['sandbox']['last_uid'] = 1;
    }

    $limit = 20;
    $uids = $userStorage
      ->getQuery()
      ->sort('uid')
      ->condition('uid', $context['sandbox']['last_uid'], '>')
      ->range(0, $limit)
      ->execute();

    $accounts = $userStorage->loadMultiple($uids);
    foreach ($accounts as $account) {
      if (\Drupal::service('crm_core_user_sync.relation')->relate($account)) {
        $context['results']['synced']++;
      }
      $context['sandbox']['last_uid'] = $account->id();
      $context['sandbox']['progress']++;
    }

    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }

  /**
   * Batch finished callback.
   */
  public static function finished($success, $results, $operations) {
    \Drupal::messenger()->addMessage(t('@count users have been associated with contacts.', ['@count' => $results['synced']]));
  }

}
