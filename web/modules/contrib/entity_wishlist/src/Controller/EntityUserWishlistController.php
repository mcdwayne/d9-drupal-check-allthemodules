<?php

namespace Drupal\entity_wishlist\Controller;

/**
 * @file
 * Contains \Drupal\entity_wishlist\Controller\EntityUserWishlistController.
 */


use Drupal\Core\Controller\ControllerBase;
use Drupal\user\UserInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Database\Connection;

/**
 * A EntityUserWishlistController controller.
 */
class EntityUserWishlistController extends ControllerBase implements ContainerInjectionInterface {

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
  public function content(UserInterface $user) {
    $uid = $this->account->id();
    $header = [
      ['data' => $this->t('No.')],
      ['data' => $this->t('Title')],
    ];
    $db = $this->database;
    $query = $db->select('entity_wishlist', 'ew');
    $query->fields('ew', ["wid", "entity_id"]);
    $query->condition('uid', $uid, "=");
    $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(10);
    $list_records = $pager->execute()->fetchAll();
    $rows = [];
    foreach ($list_records as $key => $data) {
      $node_storage = $this->entityTypeManager()->getStorage('node');
      $node = $node_storage->load($data->entity_id);
      $node_title = $node->getTitle();
      $path = "/node/" . $data->entity_id;
      $url = Url::fromUri('internal:' . $path);
      $link = Link::fromTextAndUrl($node_title, $url);
      $rows[] = [
        'data' => [
          'name' => $key + 1,
          'content' => $link,
        ],
      ];
    }
    $build['config_table'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      "#empty" => $this->t("No record found."),
    ];
    $build['pager'] = [
      '#type' => 'pager',
    ];
    return $build;
  }

}
