<?php

namespace Drupal\ptalk;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Database\Connection;

/**
 * Message manager contains common functions to manage message data.
 */
class MessageManager implements MessageManagerInterface {

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Active database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Construct the MessageManager object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(EntityManagerInterface $entity_manager, AccountInterface $current_user, Connection $database) {
    $this->entityManager = $entity_manager;
    $this->currentUser = $current_user;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public function isMessageDeleted($mid, $account = NULL) {
    $query = $this->database->select('ptalk_message_index', 'pmi');
    $query->addField('pmi', 'mid');
    $query->condition('pmi.mid', $mid);
    $query->condition('pmi.deleted', 0, '=');

    if ($account) {
      $query
        ->condition('pmi.recipient', $account->id());
    }

    $not_deleted = $query
      ->execute()
      ->fetchField();

    return (bool) $not_deleted;
  }

}
