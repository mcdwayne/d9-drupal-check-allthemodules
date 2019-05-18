<?php

namespace Drupal\friendship;

use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\friendship\Entity\Friendship;

/**
 * Class FriendshipService.
 *
 * @package Drupal\friendship
 */
class FriendshipService implements FriendshipInterface {

  /**
   * Current logged user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $currentUser;

  /**
   * DB connection.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $connection;

  /**
   * Constructor.
   */
  public function __construct() {
    $this->currentUser = User::load(\Drupal::currentUser()->id());
    $this->connection = \Drupal::database();
  }

  /**
   * {@inheritdoc}
   */
  public function follow(User $target_user) {
    $friendship = Friendship::create([
      'uid' => $this->currentUser->id(),
      'requested_uid' => $target_user->id(),
      'status' => 0,
    ]);

    $friendship->save();

    $friendship = Friendship::create([
      'uid' => $target_user->id(),
      'requested_uid' => $this->currentUser->id(),
      'status' => -1,
    ]);

    $friendship->save();
  }

  /**
   * {@inheritdoc}
   */
  public function unfollow(User $target_user) {
    $entity_ids = $this->getFriendshipEntityIds($target_user);

    if ($entity_ids) {
      $storage_handler = \Drupal::entityTypeManager()
        ->getStorage('friendship');
      $entities = $storage_handler
        ->loadMultiple($entity_ids);
      $storage_handler
        ->delete($entities);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function accept(User $target_user) {
    $entity_ids = $this->getFriendshipEntityIds($target_user);

    if ($entity_ids) {
      $storage_handler = \Drupal::entityTypeManager()
        ->getStorage('friendship');
      $entities = $storage_handler
        ->loadMultiple($entity_ids);

      /** @var \Drupal\friendship\Entity\Friendship $friendship */
      foreach ($entities as $friendship) {
        $friendship->set('status', 1);
        $friendship->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function removeFriend(User $target_user) {
    // Delete friends and made his your subscribers.
    $query = \Drupal::entityQuery('friendship');
    $query->condition('uid', $this->currentUser->id())
      ->condition('requested_uid', $target_user->id());
    $result = $query->execute();

    $entity_id = array_shift($result);
    $friendship = Friendship::load($entity_id);

    if ($friendship) {
      $friendship->set('status', -1);
      $friendship->save();
    }

    $query = \Drupal::entityQuery('friendship');
    $query->condition('uid', $target_user->id())
      ->condition('requested_uid', $this->currentUser->id());
    $result = $query->execute();

    $entity_id = array_shift($result);
    $friendship = Friendship::load($entity_id);

    if ($friendship) {
      $friendship->set('status', 0);
      $friendship->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function decline(User $target_user) {
    $entity_ids = $this->getFriendshipEntityIds($target_user);

    if ($entity_ids) {
      $storage_handler = \Drupal::entityTypeManager()
        ->getStorage('friendship');
      $entities = $storage_handler
        ->loadMultiple($entity_ids);
      $storage_handler
        ->delete($entities);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isRequestSend(User $target_user) {
    $result = $this->getFriendshipRow($this->currentUser, $target_user);

    // @todo made it more elegance.
    if (isset($result[0]->status) && $result[0]->status == 0) {
      $result = $this->getFriendshipRow($target_user, $this->currentUser);
      // @todo made it more elegance.
      if (isset($result[0]->status) && $result[0]->status == -1) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isFriend(User $target_user) {
    $result = $this->getFriendshipRow($this->currentUser, $target_user);

    // @todo made it more elegance.
    if (isset($result[0]->status) && $result[0]->status == 1) {
      $result = $this->getFriendshipRow($target_user, $this->currentUser);
      // @todo made it more elegance.
      if (isset($result[0]->status) && $result[0]->status == 1) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isFollowedYou(User $target_user) {
    $result = $this->getFriendshipRow($this->currentUser, $target_user);

    // @todo made it more elegance.
    if (isset($result[0]->status) && $result[0]->status == -1) {
      $result = $this->getFriendshipRow($target_user, $this->currentUser);
      // @todo made it more elegance.
      if (isset($result[0]->status) && $result[0]->status == 0) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isHasRelationship(User $target_user) {
    $result = $this->getFriendshipRow($this->currentUser, $target_user);

    if (empty($result)) {
      $result = $this->getFriendshipRow($target_user, $this->currentUser);

      if (empty($result)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Select all friends row.
   *
   * @param \Drupal\user\Entity\User $current_user
   *   Current user.
   * @param \Drupal\user\Entity\User $target_user
   *   Target user.
   *
   * @return object
   *   Result object.
   */
  protected function getFriendshipRow(User $current_user, User $target_user) {
    /** @var \Drupal\Core\Database\Driver\mysql\Select $query */
    $query = $this->connection->select('friendship', 'fr')
      ->fields('fr', ['status'])
      ->condition('fr.uid', $current_user->id())
      ->condition('fr.requested_uid', $target_user->id());

    $result = $query->execute()->fetchAll();

    return $result;
  }

  /**
   * Get friendship entity_id by requested user.
   *
   * @param \Drupal\user\Entity\User $target_user
   *   Target user.
   *
   * @return array
   *   Entity ids.
   */
  protected function getFriendshipEntityIds(User $target_user) {
    $query = \Drupal::entityQuery('friendship');

    $user_ids = [$this->currentUser->id(), $target_user->id()];

    // Get all relationship between this users.
    $query->condition('uid', $user_ids, 'IN')
      ->condition('requested_uid', $user_ids, 'IN');

    $result = $query->execute();

    return $result;
  }

  /**
   * Get link attributes.
   *
   * @param \Drupal\user\Entity\User $target_user
   *   Target user.
   *
   * @return array
   *   Return link attributes.
   */
  public function getLinkAttributes(User $target_user) {
    $config = \Drupal::config('friendship.settings');

    if ($this->isRequestSend($target_user)) {
      $link_attributes = [
        '#title' => $config->get('button.unfollow_text'),
        '#url' => Url::fromRoute('friendship.unfollow', [
          'uid' => $target_user->id(),
          'js' => 'nojs',
        ]),
      ];
    }
    elseif ($this->isFollowedYou($target_user)) {
      $link_attributes = [
        '#title' => $config->get('button.accept_text'),
        '#url' => Url::fromRoute('friendship.accept', [
          'uid' => $target_user->id(),
          'js' => 'nojs',
        ]),
      ];
    }
    elseif ($this->isFriend($target_user)) {
      $link_attributes = [
        '#title' => $config->get('button.remove_friend_text'),
        '#url' => Url::fromRoute('friendship.removeFriend', [
          'uid' => $target_user->id(),
          'js' => 'nojs',
        ]),
      ];
    }
    else {
      $link_attributes = [
        '#title' => $config->get('button.follow_text'),
        '#url' => Url::fromRoute('friendship.follow', [
          'uid' => $target_user->id(),
          'js' => 'nojs',
        ]),
      ];
    }

    return $link_attributes;
  }

  /**
   * Render friendship process link.
   *
   * @param \Drupal\user\Entity\User $target_user
   *   Target user.
   *
   * @return array
   *   Array with link.
   */
  public function getProcessLink(User $target_user) {
    /** @var \Drupal\user\Entity\User $current_user */
    $current_user = \Drupal::currentUser();

    $build = [];

    if ($target_user->id() != $current_user->id() &&
      $current_user->hasPermission('use friendship workflow') &&
      $current_user->id() != 0) {
      $friendship = \Drupal::service('friendship.friendship_service');

      $id_hash = md5($target_user->id() + rand());
      $build = [
        '#type' => 'link',
        '#attributes' => [
          'class' => [
            'use-ajax',
            'friendship-ajax-link-' . $target_user->id(),
          ],
          'id' => 'friendship-ajax-link-' . $id_hash,
        ],
        '#attached' => [
          'library' => [
            'core/drupal.ajax',
            'friendship/process-link',
          ],
        ],
        '#cache' => [
          'max-age' => 0,
        ],
      ];

      $link_attributes = $friendship->getLinkAttributes($target_user);
      $build += $link_attributes;
    }

    return $build;
  }

}
