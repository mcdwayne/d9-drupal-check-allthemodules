<?php

namespace Drupal\recently_read;

use Drupal\Core\Session\SessionManager;
use Drupal\recently_read\Entity\RecentlyRead;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Config\ConfigFactory;

/**
 * Recently read service.
 */
class RecentlyReadService {

  /**
   * The current user injected into the service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  private $currentUser;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * SessionManager service.
   *
   * @var \Drupal\Core\Session\SessionManager
   */
  private $sessionManager;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  private $configFactory;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\Session\SessionManager $sessionManager
   *   Session manager.
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   Config factory service.
   */
  public function __construct(
    AccountInterface $current_user,
    EntityTypeManagerInterface $entity_type_manager,
    SessionManager $sessionManager,
    ConfigFactory $configFactory
  ) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->sessionManager = $sessionManager;
    $this->configFactory = $configFactory;
  }

  /**
   * Custom function to insert or update an entry for recently read.
   */
  public function insertEntity($entity) {
    // Get configuration and check if RR delete options is count based.
    $config = $this->configFactory->getEditable('recently_read.configuration');
    $maxRecords = NULL;
    if ($config->get('delete_config') == "count") {
      $maxRecords = $config->get('count');
    }
    $user_id = $this->currentUser->id();
    // If anonymous set user_id to 0 and check for any existing entries.
    if ($this->currentUser->isAnonymous()) {
      // Ensure something is in $_SESSION, otherwise the session ID will
      // not persist.
      // TODO: Replace this with something cleaner once core provides it.
      // See https://www.drupal.org/node/2865991.
      if (!isset($_SESSION['recently_read'])) {
        $_SESSION['recently_read'] = TRUE;
        $this->sessionManager->start();
      }
      $user_id = 0;
      $exists = $this->entityTypeManager->getStorage('recently_read')
        ->loadByProperties([
          'session_id' => $this->sessionManager->getId(),
          'type' => $entity->getEntityTypeId(),
          'entity_id' => $entity->id(),
        ]);
    }
    else {
      $exists = $this->entityTypeManager->getStorage('recently_read')
        ->loadByProperties([
          'user_id' => $user_id,
          'type' => $entity->getEntityTypeId(),
          'entity_id' => $entity->id(),
        ]);
    }
    // If exists then update created else create new.
    if (!empty($exists)) {
      foreach ($exists as $entry) {
        $entry->setCreatedTime(time())->save();
      }
    }
    else {
      // Create new.
      $recentlyRead = $this->entityTypeManager->getStorage('recently_read')
        ->create([
          'type' => $entity->getEntityTypeId(),
          'user_id' => $user_id,
          'entity_id' => $entity->id(),
          'session_id' => $user_id ? 0 : $this->sessionManager->getId(),
          'created' => time(),
        ]);
      $recentlyRead->save();
    }
    // Delete records if there is a limit.
    $userRecords = $this->getRecords($user_id);
    if ($maxRecords && count($userRecords) > $maxRecords) {
      $records = array_slice($userRecords, $maxRecords, count($userRecords));
      $this->deleteRecords($records);
    }
  }

  /**
   * Delete records from DB.
   *
   * @param array $records
   *   Number of records to delete.
   */
  public function deleteRecords(array $records) {
    foreach ($records as $rid) {
      // Delete data.
      $recently_read = RecentlyRead::load($rid);
      $recently_read->delete();
    }
  }

  /**
   * Get all records in DB for specified user/anonymous.
   *
   * @param int $user_id
   *   User id.
   *
   * @return array|int
   *   Returns an array of record id's.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function getRecords($user_id) {
    if ($user_id != 0) {
      $records = $this->entityTypeManager->getStorage('recently_read')
        ->getQuery()
        ->condition('user_id', $user_id)
        ->sort('created', 'DESC')
        ->execute();
    }
    else {
      $records = $this->entityTypeManager->getStorage('recently_read')
        ->getQuery()
        ->condition('session_id', session_id())
        ->sort('created', 'DESC')
        ->execute();
    }
    return $records;
  }

}
