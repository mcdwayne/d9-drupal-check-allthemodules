<?php

namespace Drupal\acsf\Event;

/**
 * Handles the scrubbing of Drupal entities.
 *
 * This is an abstract class / is unusable as-is, only because entityTypeId is
 * not set automatically.
 */
abstract class AcsfDuplicationScrubEntityHandler extends AcsfEventHandler {

  /**
   * The entity type to scrub.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The entity manager.
   *
   * This is not properly injected at the moment, but already having it in a
   * variable and setting it on construction makes this easier, and makes
   * writing child methods more compatible, for the future.
   * Called $entityTypeManager because that naming is actually more appropriate.
   * Unfortunately, EntityTypeManager(Interface) only exists since 8.0.0-rc3.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler.
   *
   * This is not properly injected at the moment, but already having it in a
   * variable and setting it on construction makes this easier, and makes
   * writing child methods more compatible, for the future.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The ACSF variable storage.
   *
   * This is not properly injected at the moment, but already having it in a
   * variable and setting it on construction makes this easier, and makes
   * writing child methods more compatible, for the future.
   *
   * @var \Drupal\acsf\AcsfVariableStorage
   */
  protected $acsfVarStorage;

  /**
   * Constructor.
   *
   * @param AcsfEvent $event
   *   The event that has been initiated.
   */
  public function __construct(AcsfEvent $event) {
    // Fake-inject some objects.
    $this->entityTypeManager = \Drupal::entityManager();
    $this->moduleHandler = \Drupal::moduleHandler();
    $this->acsfVarStorage = \Drupal::service('acsf.variable_storage');

    parent::__construct($event);
  }

  /**
   * Implements AcsfEventHandler::handle().
   */
  public function handle() {
    $this->consoleLog(dt('Entered @class', ['@class' => get_class($this)]));

    $options = $this->event->context['scrub_options'];
    $limit = $options['batch_' . $this->entityTypeId];
    $var_name = 'acsf_duplication_scrubbed_' . $this->entityTypeId;
    $entity_type = $this->entityTypeManager->getDefinition($this->entityTypeId);

    do {
      // Get a range of entities we have not processed yet (i.e. do not query
      // entities that could not be deleted, multiple times.)
      // Deleting the highest entity first probably makes for less processing
      // for some entity types (e.g. for comments, since delete() does not need
      // to deal with comments that would be orphaned).
      $ids = $this->getBaseQuery()
        ->range(0, $limit)
        ->sort($entity_type->getKey('id'), 'DESC')
        ->execute();

      if ($ids) {
        // Delete the entities, one by one. This may be slower than mass
        // deleting them, but this way we can catch an exception without a mass
        // delete being fully rolled back.
        $entities = $this->entityTypeManager->getStorage($this->entityTypeId)
          ->loadMultiple($ids);
        $this->deleteEntities($entities);

        $this->acsfVarStorage->set($var_name, min($ids), 'acsf_duplication_scrub');
      }
      else {
        // Make sure we won't run a query for the same IDs again.
        $this->acsfVarStorage->set($var_name, 0, 'acsf_duplication_scrub');
        break;
      }

      // If out-of-memory protection is not set, we loop until all items are
      // processed.
      if ($options['avoid_oom']) {
        $this->event->dispatcher->interrupt();
        break;
      }

    } while (TRUE);

  }

  /**
   * Deletes entities.
   *
   * @param array $entities
   *   The entities to delete.
   */
  protected function deleteEntities(array $entities) {
    // Delete the entities one by one. This may be slower than mass deleting
    // them, but this way we can catch an exception without a mass delete
    // being fully rolled back.
    foreach ($entities as $entity) {
      try {
        $entity->delete();
      }
      catch (\Exception $e) {
        // OK, we'll live with not scrubbing this.
      }
    }
  }

  /**
   * Gets an initialized entity query instance.
   *
   * When calling this function repeatedly, a filter is applied such that the
   * query will return different IDs for each consecutive query.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The entity query instance.
   */
  protected function getBaseQuery() {
    $entity_type = $this->entityTypeManager->getDefinition($this->entityTypeId);
    $entity_query = $this->entityTypeManager->getStorage($this->entityTypeId)
      ->getQuery();

    $var_name = 'acsf_duplication_scrubbed_' . $this->entityTypeId;
    $last_processed_id = $this->acsfVarStorage->get($var_name, -1);
    if ($last_processed_id != -1) {
      $entity_query->condition($entity_type->getKey('id'), $last_processed_id, '<');
    }

    return $entity_query;
  }

  /**
   * Gets a list of user IDs which should not be scrubbed.
   *
   * This is a reference implementation for use by child classes; by default, it
   * returns admins and the anonymous user.
   *
   * @return array
   *   An indexed array of user IDs which should not be scrubbed.
   */
  protected function getPreservedUsers() {
    // Preserve site admins.
    $preserved = $this->getSiteAdmins();
    if (array_search(1, $preserved) === FALSE) {
      // Preserve UID 1.
      $preserved[] = 1;
    }
    // Preserve the anonymous user.
    $preserved[] = 0;
    $this->moduleHandler->alter('acsf_duplication_scrub_preserved_users', $preserved);
    return $preserved;
  }

  /**
   * Gets a list of site admins.
   *
   * @return array
   *   An indexed array of user IDs representing site admins.
   */
  public function getSiteAdmins() {
    $uids = [];

    $admin_roles = $this->entityTypeManager->getStorage('user_role')->getQuery()
      ->condition('is_admin', TRUE)
      ->execute();
    $this->moduleHandler->alter('acsf_duplication_scrub_admin_roles', $admin_roles);

    if (!empty($admin_roles)) {
      $uids = $this->entityTypeManager->getStorage('user')
        ->getQuery()
        ->condition('roles', $admin_roles, 'IN')
        ->execute();
    }

    return $uids;
  }

  /**
   * Counts the entities that still need to be processed.
   *
   * @return int
   *   The number of entities that still need to be processed. (That is: not
   *   counting the entities that could not be deleted.)
   */
  public function countRemaining() {
    return $this->getBaseQuery()->count()->execute();
  }

}
