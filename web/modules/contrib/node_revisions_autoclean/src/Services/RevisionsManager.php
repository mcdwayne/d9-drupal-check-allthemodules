<?php

namespace Drupal\node_revisions_autoclean\Services;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * Class RevisionsManager.
 */
class RevisionsManager {
  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Queue\QueueFactory definition.
   *
   * @var Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;
  /**
   * Drupal\Core\Database\Connection.
   *
   * @var Drupal\Core\Database\Connection
   */
  protected $database;
  /**
   * Drupal\Core\Language\LanguageManager.
   *
   * @var Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * Constructs a new RevisionsManager object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, QueueFactory $queueFactory, Connection $database, LanguageManagerInterface $languageManager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->queueFactory = $queueFactory;
    $this->database = $database;
    $this->languageManager = $languageManager;
  }

  /**
   * Loads revisions of a node.
   *
   * @param Drupal\node\Entity\Node $entity
   *   Node to load revisions.
   * @param string $langcode
   *   The langcode.
   *
   * @return Drupal\node\Entity\Node[]
   *   Returns all revisions.
   */
  public function loadRevisions(Node $entity, $langcode = NULL) {
    $vids = $this->revisionIds($entity, $langcode);
    $revisions = [];
    foreach ($vids as $vid) {
      $revisions[] = $this->entityTypeManager->getStorage('node')->loadRevision($vid);
    }

    return $revisions;
  }

  /**
   * Loads revisions IDs by langcode.
   *
   * @param Drupal\node\NodeInterface $node
   *   The node.
   * @param string $langcode
   *   The langcode.
   *
   * @return mixed
   *   Array of revisions.
   */
  public function revisionIds(NodeInterface $node, $langcode = NULL) {
    if (isset($langcode)) {
      return $this->database->query(
        'SELECT vid FROM {node_revision} WHERE nid=:nid AND langcode=:langcode ORDER BY vid',
        [
          ':nid' => $node->id(),
          ':langcode' => $langcode,
        ]
      )->fetchCol();
    }
    else {
      return $this->database->query(
        'SELECT vid FROM {node_revision} WHERE nid=:nid ORDER BY vid',
        [
          ':nid' => $node->id(),
        ]
      )->fetchCol();
    }

  }

  /**
   * Loads revisions to delete of a node according to settings.
   *
   * @param Drupal\node\Entity\Node $node
   *   The node.
   * @param Drupal\node\Entity\Node[] $revisions
   *   Revisions if they are already loaded.
   *
   * @return array
   *   Returns all revisions IDs to delete.
   */
  public function revisionsToDelete(Node $node, array $revisions = []) {
    $settings = \Drupal::config('node_revisions_autoclean.settings');
    $max = $settings->get('node.' . $node->bundle());
    $szDi = $settings->get('interval.' . $node->bundle());
    $minDate = FALSE;
    try {
      $di = new \DateInterval($szDi);
      $minDate = new \DateTime();
      $minDate->sub($di);
    }
    catch (\Exception $exc) {

    }
    $result = [];
    if ($max) {
      $languages = $this->languageManager->getLanguages();
      /* @var $language \Drupal\Core\Language\LanguageInterface */
      foreach ($languages as $language) {
        $revisions = $this->loadRevisions($node, $language->getId());
        $revisions = array_reverse($revisions);
        $count = 0;
        $bStart = FALSE;
        /* @var $revision Node */
        foreach ($revisions as $revision) {
          if ($bStart) {
            $count++;
          }
          if ($revision->get('status')->value) {
            $bStart = TRUE;
          }
          if ($minDate && $minDate instanceof \DateTime && $count > $max) {
            if ($revision->getRevisionCreationTime() < $minDate->getTimestamp()) {
              $result[] = $revision->vid->value;
            }
          }
          elseif ($count > $max) {
            $result[] = $revision->vid->value;
          }
        }

      }
    }

    return $result;
  }

  /**
   * Deletes a revision.
   *
   * @param int $revisionID
   *   Revision ID to delete.
   */
  public function deleteRevision($revisionID) {
    $this->deleteRevisions([$revisionID]);
  }

  /**
   * Deletes revisions.
   *
   * @param int[] $revisionsIDs
   *   Revisions IDs to delete.
   */
  public function deleteRevisions(array $revisionsIDs) {
    foreach ($revisionsIDs as $revisionID) {
      try {
        $this->entityTypeManager->getStorage('node')
          ->deleteRevision($revisionID);
      }
      catch (EntityStorageException $e) {
        watchdog_exception('node_revisions_autoclean', $e);
      }
    }
  }

  /**
   * Queues a node for cronjob.
   *
   * @param Drupal\node\Entity\Node $node
   *   The node.
   */
  public function queueNodeForCronJob(Node $node) {
    $queue = $this->queueFactory->get('cleanup_revisions_worker');
    $queue->createItem((object) [
      'node' => $node,
    ]);
  }

}
