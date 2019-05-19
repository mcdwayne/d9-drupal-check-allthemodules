<?php

namespace Drupal\yac_referral;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\Entity\User;

/**
 * Class ReferralBatchHandlers.
 *
 * Handles the batch actions for Yac referral module.
 *
 * @package Drupal\yac_referral\ReferralBatchHandlers
 * @author Alessandro Cereda <alessandro@geekworldesign.com>
 * @group yac_referral
 */
class ReferralBatchHandlers {

  use DependencySerializationTrait;

  /**
   * Adds the affiliate role to a User entity.
   *
   * @param \Drupal\user\Entity\User $user
   *   The User that will gain the affiliate role.
   */
  private static function affiliateAddRole(User $user) {
    $user->addRole('affiliate');
    $user->save();
  }

  /**
   * Removes the affiliate role from a User entity.
   *
   * @param \Drupal\user\Entity\User $user
   *   The User that will lose the affiliate role.
   */
  private static function affiliateRemoveRole(User $user) {
    $user->removeRole('affiliate');
    $user->save();
  }

  /**
   * Handles the batch processing for adding the affiliaes role to all users.
   *
   * @param \Drupal\user\Entity\User[] $users
   *   An array of Users entities.
   * @param mixed $context
   *   The context of the bach.
   */
  public static function bulkAddAffiliates(array $users, &$context) {
    if (!isset($context['results']['users'])) {
      $context['results']['users'] = [];
    }
    if (!$users) {
      return;
    }
    $sandbox = $context['sandbox'];
    if (empty($sandbox)) {
      $sandbox['progress'] = 0;
      $sandbox['max'] = count($users);
      $sandbox['users'] = $users;
    }
    foreach ($sandbox['users'] as $user) {
      if (!$user->hasRole('admin')) {
        $context['message'] = t('Making @name an affiliate', [
          '@name' => $user->getUsername(),
        ]);
        self::affiliateAddRole($user);
        $context['results']['users'][] = $user->getUsername();
      }
      $sandbox['progress']++;
    }
    $context['finished'] = $sandbox['progress'] / $sandbox['max'];
  }

  /**
   * Handles the batch processing for removing the affiliaes role from users.
   *
   * @param \Drupal\user\Entity\User[] $users
   *   An array of Users entities.
   * @param mixed $context
   *   The context of the bach.
   */
  public static function bulkRemoveAffiliates(array $users, &$context) {
    if (!isset($context['results']['users'])) {
      $context['results']['users'] = [];
    }
    if (count($users) === 0) {
      return;
    }
    $sandbox = $context['sandbox'];
    if (empty($sandbox)) {
      $sandbox['progress'] = 0;
      $sandbox['max'] = count($users);
      $sandbox['users'] = $users;
    }
    foreach ($sandbox['users'] as $user) {
      $context['message'] = t('@name will lose its affiliate role', [
        '@name' => $user->getUsername(),
      ]);
      self::affiliateRemoveRole($user);
      $context['results']['user'][] = $user->getUsername();
      $sandbox['progress']++;
    }
    $context['finished'] = $sandbox['progress'] / $sandbox['max'];
  }

  /**
   * Handles the messages that will appear during batch operations.
   *
   * @param string $type
   *   The type of message you are requesting.
   * @param array $results
   *   An array with the object involved in the bulk operation.
   * @return void
   */
  public function bulkAffiliateMessages(string $type, array $results) {
    if ('finished-add' === $type) {
      $message = \Drupal::translation()->formatPlural(
        count($results),
        'Added Affiliate role to a user',
        'Added affilaite role to @count users.'
      );
    }
    elseif ('finished-remove' === $type) {
      $message = \Drupal::translation()->formatPlural(
        count($results),
        'Removed Affiliate role from a user',
        'Removed affilaite role from @count users.'
      );
    }
    else {
      return NULL;
    };
    return $message;
  }

  /**
   * Error message for bulk operations.
   *
   * @param string $messagee
   *   The message used that will explain the bulk error.
   */
  public function bulkError(string $message) {
    drupal_set_message($message, 'error');
  }

  /**
   * Set messages for when the bulk add finsh.
   *
   * @param bool $success
   *   Indicates if the batch finished correctly.
   * @param array $results
   *   The results of the search.
   * @param array $operations
   *   An array of actions performed on the batch file.
   */
  public function bulkAddFinished($success, array $results, array $operations) {
    if ($success) {
      drupal_set_message($this->bulkAffiliateMessages('finished-add', $results));
    }
    else {
      $message = t('Ops! An error occured while adding roles.');
      $this->bulkError($message);
    }
  }

  /**
   * Set messages for when the bulk remove finsh.
   *
   * @param bool $success
   *   Indicates if the batch finished correctly.
   * @param array $results
   *   The results of the search.
   * @param array $operations
   *   An array of actions performed on the batch file.
   */
  public function bulkRemoveFinished($success, array $results, array $operations) {
    if ($success) {
      drupal_set_message($this->bulkAffiliateMessages('finished-remove', $results));
    }
    else {
      $message = t('Ops! An error occured while removing roles.');
      $this->bulkError($message);
    }
  }

}
