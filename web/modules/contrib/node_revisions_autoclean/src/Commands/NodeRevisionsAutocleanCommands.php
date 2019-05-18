<?php

namespace Drupal\node_revisions_autoclean\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node_revisions_autoclean\Services\RevisionsManager;
use Drush\Commands\DrushCommands;

/**
 * Class NodeRevisionsAutocleanCommands.
 *
 * @package Drupal\node_revisions_autoclean\Commands
 */
class NodeRevisionsAutocleanCommands extends DrushCommands {
  use StringTranslationTrait;

  /**
   * Drupal\Core\Entity\EntityTypeManager.
   *
   * @var Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;
  /**
   * Drupal\node_revisions_autoclean\Services\RevisionsManager.
   *
   * @var Drupal\node_revisions_autoclean\Services\RevisionsManager
   */
  protected $revisionsManager;

  /**
   * NodeRevisionsAutocleanCommands constructor.
   *
   * @param Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   EntityTypeManager.
   * @param Drupal\node_revisions_autoclean\Services\RevisionsManager $revisionsManager
   *   RevisionsManager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, RevisionsManager $revisionsManager) {
    parent::__construct();
    $this->entityTypeManager = $entityTypeManager;
    $this->revisionsManager = $revisionsManager;
  }

  /**
   * Deletes old revisions according to site's settings.
   *
   * @command nra-delete-old-revisions
   * @validate-module-enabled node
   * @aliases nra:dor
   */
  public function deleteRevisionsAccordingSiteSettings() {
    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple();
    $count = 0;
    foreach ($nodes as $node) {
      $revisions = $this->revisionsManager->revisionsToDelete($node);
      if (count($revisions)) {
        $this->revisionsManager->deleteRevisions($revisions);
        $this->logger()->log('success', $this->t('@count revisions deleted for node @nid : @label', [
          '@count' => count($revisions),
          '@nid' => $node->id(),
          '@label' => $node->label(),
        ]));
      }
      $count += count($revisions);
    }
    $this->logger()->log('success', $this->t('Global : @count revisions deleted.', [
      '@count' => $count,
    ]));
  }

}
