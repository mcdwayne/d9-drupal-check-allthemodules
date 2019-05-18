<?php

namespace Drupal\ptalk;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the storage handler class for messages.
 *
 * Extends the Drupal\Core\Entity\Sql\SqlContentEntityStorage class.
 */
class MessageStorage extends SqlContentEntityStorage implements MessageStorageInterface {

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
   * Constructs a MessageStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_info
   *   An array of entity info for the entity type.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Cache backend instance to use.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(EntityTypeInterface $entity_info, Connection $database, EntityManagerInterface $entity_manager, AccountInterface $current_user, CacheBackendInterface $cache, LanguageManagerInterface $language_manager) {
    parent::__construct($entity_info, $database, $entity_manager, $cache, $language_manager);
    $this->database = $database;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_info) {
    return new static(
      $entity_info,
      $container->get('database'),
      $container->get('entity.manager'),
      $container->get('current_user'),
      $container->get('cache.entity'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function buildQuery($ids, $revision_id = FALSE) {
    $query = parent::buildQuery($ids, $revision_id);
    $query->join('ptalk_message_index', 'pmi', "base.mid = pmi.mid");

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getNumPage($message, $per_page = 1, $page = '', $count_deleted = FALSE, $account = NULL) {
    if (is_null($account)) {
      $account = $this->currentUser;
    }
    // Count how many messages we have to display
    // depending fo the received parameters.
    $count_query = $this->database->select('ptalk_message', 'pm');
    $count_query->addExpression('COUNT(DISTINCT pm.mid)');
    $count_query->join('ptalk_message_index', 'pmi', 'pm.mid = pmi.mid');
    $count_query->condition('pm.tid', $message->getThreadId());
    $count_query->condition('pmi.recipient', $account->id());

    if ($page == 'message') {
      $count_query->condition('pmi.mid', $message->id(), '<');
    }

    if (!$count_deleted) {
      // Do not count deleted messages.
      $count_query->condition('pmi.deleted', 0);
    }

    $count = $page == 'message' ? $count_query->execute()->fetchField() : $count_query->execute()->fetchField() - 1;

    // Calculate to which page we should redirect.
    $page = ($per_page > 1) ? floor($count / $per_page) : $count;

    return $page;
  }

  /**
   * {inheritdoc}
   */
  public function loadIndex($mids) {
    $config = \Drupal::config('ptalk.settings');
    $message_status = $config->get('ptalk_message_status');
    $query = $this->database->select('ptalk_message_index', 'pmi');
    $query->join('ptalk_message', 'pm', 'pmi.mid = pm.mid');
    $query->fields('pmi', ['mid', 'deleted']);
    $query->addTag('ptalk_message_index');
    // If message status is disabled, we do not need all this information.
    if ($message_status) {
      $limit = $config->get('ptalk_message_status_limit_recipients');
      $query->addExpression("IF(pm.author = :current_user,
                                (SELECT SUBSTRING_INDEX(GROUP_CONCAT(DISTINCT CONCAT(u.name, ':', pmie.status) ORDER BY pmie.status DESC), ',', :limit)
                                 FROM {ptalk_message_index} pmie
                                 JOIN {users_field_data} u ON pmie.recipient = u.uid
                                 WHERE pmie.mid = pm.mid AND pmie.recipient <> :current_user AND pmie.status <> 1),
                                (SELECT CONCAT(u.name, ':', pmie.status)
                                 FROM {ptalk_message_index} pmie
                                 JOIN {users_field_data} u ON pmie.recipient = u.uid
                                 WHERE pmie.mid = pm.mid AND pmie.recipient = :current_user AND pmie.status <> 1))",
                            'status', [':limit' => $limit, ':current_user' => $this->currentUser->id()]);
    }
    else {
      $query->addField('pmi', 'status');
    }

    return $query
      ->condition('pmi.mid', $mids, 'IN')
      ->condition('pmi.recipient', $this->currentUser->id())
      ->orderBy('pmi.mid')
      ->execute()
      ->fetchAllAssoc('mid');
  }

}
