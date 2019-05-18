<?php

namespace Drupal\acsf\Event;

/**
 * Handles the scrubbing of Drupal comments.
 */
class AcsfDuplicationScrubCommentHandler extends AcsfDuplicationScrubEntityHandler {

  /**
   * Constructor.
   *
   * @param AcsfEvent $event
   *   The event that has been initiated.
   */
  public function __construct(AcsfEvent $event) {
    $this->entityTypeId = 'comment';
    parent::__construct($event);
  }

  /**
   * Implements AcsfEventHandler::handle().
   */
  public function handle() {
    $options = $this->event->context['scrub_options'];
    if ($options['retain_content']
            || !\Drupal::moduleHandler()->moduleExists('comment')) {
      // We still want to log that we were here.
      $this->consoleLog(dt('Entered @class', ['@class' => get_class($this)]));
      return;
    }

    // If we're using the standard comment storage handler, replace it with
    // a handler that prefers scrubbing over consistency (i.e. in case of
    // exceptions thrown, loads things anyway).
    $original_class = get_class($this->entityTypeManager->getStorage($this->entityTypeId));
    if ($original_class === 'Drupal\comment\CommentStorage') {
      // $this->entityTypeManager->handlers contains a.o. the current storage
      // class instance which we want to change for another one. We can only do
      // this through EntityTypeManager::clearCachedDefinitions() which
      // clears:
      // - handlers (all class instances per handler type / entity type);
      // - definitions (the full entity type (plugin) definitions, including
      //   the one in the cache backend).
      // We don't want to invalidate the cached definitions, but oh well...
      $this->entityTypeManager->clearCachedDefinitions();
      // Now we want to set the (temporary) new class. For this there's only a
      // method in the entity type, not in the manager. The (plugin / definition
      // class for the) entity type will always be regenerated before setting
      // its storage handler, because all definitions were cleared.
      $this->entityTypeManager->getDefinition($this->entityTypeId)
        ->setStorageClass('Drupal\acsf\Event\AcsfDuplicationScrubCommentStorage');

      // Also: try to load/delete orphaned comments by ID (not by loading the
      // entities) in a custom method.
      $limit = $options['batch_' . $this->entityTypeId];
      if ($options['avoid_oom']) {
        $var_name = 'acsf_duplication_scrubbed_' . $this->entityTypeId;
        $max_id = $this->acsfVarStorage->get($var_name, -1);
        $this->entityTypeManager->getStorage($this->entityTypeId)
          ->deleteOrphanedItems($limit, $max_id);
      }
      else {
        // If 'avoid_oom' is not set, we should delete all orphaned comments
        // now. (The limit is basically only to keep the SQL from becoming too
        // long; processing isn't hugely expensive.)
        do {
          $orphaned_ids = $this->entityTypeManager->getStorage($this->entityTypeId)
            ->deleteOrphanedItems($limit);
        } while ($orphaned_ids);
      }
    }

    parent::handle();

    // Clean up after ourselves.
    if ($original_class === 'Drupal\comment\CommentStorage') {
      $this->entityTypeManager->clearCachedDefinitions();
      $this->entityTypeManager->getDefinition($this->entityTypeId)
        ->setStorageClass($original_class);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function countRemaining() {
    if (!\Drupal::moduleHandler()->moduleExists('comment')) {
      return 0;
    }
    return parent::countRemaining();
  }

}
