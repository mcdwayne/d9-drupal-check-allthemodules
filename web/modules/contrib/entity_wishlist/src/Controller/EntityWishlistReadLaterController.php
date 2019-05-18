<?php

namespace Drupal\entity_wishlist\Controller;

/**
 * @file
 * Contains \Drupal\entity_wishlist\Controller\EntityWishlistReadLaterController.
 */


use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;

/**
 * A EntityWishlistReadLater controller.
 */
class EntityWishlistReadLaterController extends ControllerBase {
  protected $account;
  protected $connection;

  /**
   * {@inheritdoc}
   */
  public function __construct(AccountInterface $account, Connection $connection) {
    $this->account = $account;
    $this->database = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {

    return new static(
      $container->get('current_user'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function content(NodeInterface $node) {
    $uid = $this->account->id();
    $nid = $node->id();
    $db = $this->database;
    $query = $db->select('entity_wishlist', 'ew');
    $query->fields('ew', ["wid"]);
    $query->condition('entity_id', $nid, "=");
    $query->condition('uid', $uid, "=");
    $check_entry = $query->execute()->FetchField();
    if ($check_entry) {
      $query = $this->database->delete('entity_wishlist');
      $query->condition('entity_id', $nid, "=");
      $query->condition('uid', $uid, "=");
      $query->execute();
      drupal_set_message($this->t("Item removed from your content wishlist."));
    }
    else {
      db_insert('entity_wishlist')
        ->fields(
          [
            'entity_id' => $nid,
            'uid' => $uid,
            'entity_type' => 'node',
          ]
      )->execute();
      drupal_set_message($this->t("Item added to your content wishlist."));
    }
    drupal_flush_all_caches();

    return new RedirectResponse($this->getUrlGenerator()->generateFromRoute("user.page"));
  }

}
