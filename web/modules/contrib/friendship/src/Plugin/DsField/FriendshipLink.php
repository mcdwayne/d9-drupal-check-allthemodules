<?php

namespace Drupal\friendship\Plugin\DsField;

use Drupal\ds\Plugin\DsField\DsFieldBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Friendship link add/remove/follow.
 *
 * @DsField(
 *   id = "friendship_link",
 *   title = @Translation("Friendship link."),
 *   entity_type = "user"
 * )
 * @package Drupal\friendship\Plugin\DsField
 */
class FriendshipLink extends DsFieldBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var \Drupal\user\Entity\User $target_user */
    $target_user = $this->configuration['entity'];

    /** @var \Drupal\friendship\FriendshipService $friendship */
    $friendship = \Drupal::service('friendship.friendship_service');

    return $friendship->getProcessLink($target_user);
  }

}
