<?php

namespace Drupal\acsf\Event;

/**
 * Handles the scrubbing of Drupal users.
 */
class AcsfDuplicationScrubUserHandler extends AcsfDuplicationScrubEntityHandler {

  /**
   * Constructor.
   *
   * @param AcsfEvent $event
   *   The event that has been initiated.
   */
  public function __construct(AcsfEvent $event) {
    $this->entityTypeId = 'user';
    parent::__construct($event);
  }

  /**
   * Implements AcsfEventHandler::handle().
   */
  public function handle() {
    $options = $this->event->context['scrub_options'];
    if ($options['retain_users']) {
      // We still want to log that we were here.
      $this->consoleLog(dt('Entered @class', ['@class' => get_class($this)]));
      return;
    }

    parent::handle();
  }

  /**
   * {@inheritdoc}
   */
  protected function deleteEntities(array $entities) {
    // Delete the entities one by one. This may be slower than mass deleting
    // them, but this way we can catch an exception without a mass delete
    // being fully rolled back.
    foreach ($entities as $entity) {
      try {
        $this->reassignFiles($entity->id());
        $entity->delete();
      }
      catch (\Exception $e) {
        // OK, we'll live with not scrubbing this.
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getBaseQuery() {
    $entity_query = parent::getBaseQuery();
    $entity_query->condition('uid', $this->getPreservedUsers(), 'NOT IN');

    return $entity_query;
  }

  /**
   * Reassigns files owned by the given user ID to the anonymous user.
   *
   * Prior to deleting the user, re-assign {file_managed}.uid to anonymous.
   * Re-assign files only: allow nodes and comments to be deleted. It would be
   * more proper to call File::load_multiple(), iterate each loaded file entity,
   * set its uid property, and call save() (see comment_user_cancel() for a
   * similar example for comments). It would be even more proper if file.module
   * implemented hook_user_cancel(), so we could just call that hook. But for
   * performance, we just update the {file_managed} table directly.
   *
   * @param int $uid
   *   The user ID for which to reassign files.
   */
  protected function reassignFiles($uid) {
    \Drupal::database()->update('file_managed')
      ->fields([
        'uid' => 0,
      ])
      ->condition('uid', $uid)
      ->execute();
  }

}
