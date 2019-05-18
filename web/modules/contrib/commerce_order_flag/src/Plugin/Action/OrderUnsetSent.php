<?php

namespace Drupal\commerce_order_flag\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Database\Database;

/**
 * Push term in front.
 *
 * @Action(
 *   id = "order_unset_sent",
 *   label = @Translation("Mark as unsent"),
 *   type = "commerce_order"
 * )
 */
class OrderUnsetSent extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {

    $db = Database::getConnection();

    $flags = $db->select('commerce_order_flag', 'cof')
      ->fields('cof')
      ->condition('order_id', $entity->id())
      ->execute()->fetchAll(\PDO::FETCH_ASSOC);

    if (empty($flags)){
      $db->insert('commerce_order_flag')
        ->fields([
          'value' => 0,
          'order_id' => $entity->id(),
        ])
        ->execute();
    }
    else {
      $db->update('commerce_order_flag')
        ->fields([
          'value' => 0,
          'order_id' => $entity->id(),
        ])
        ->condition('order_id', $entity->id())
        ->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {

    $user = \Drupal::currentUser();

    return $user->hasPermission('edit commerce orders flag') ? TRUE : FALSE;
  }

}
