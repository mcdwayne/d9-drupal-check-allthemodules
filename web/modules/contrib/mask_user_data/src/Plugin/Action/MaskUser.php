<?php

namespace Drupal\mask_user_data\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Mask user data.
 *
 * @Action(
 *   id = "mask_user_action",
 *   label = @Translation("Mask user data"),
 *   type = "user"
 * )
 */
class MaskUser extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities) {
    $map_array = \Drupal::config('mask_user_data.settings')->get('map_array') ?: NULL;
    foreach ($entities as $user) {
      /** @var \Drupal\user\Entity\User $user */
      \Drupal::service('mask_user_data.mask_user')->mask($user->id(), $map_array);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function execute($object = NULL) {
    $this->executeMultiple([$object]);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = AccessResult::allowed();
    return $return_as_object ? $result : $result->isAllowed();
  }

}
