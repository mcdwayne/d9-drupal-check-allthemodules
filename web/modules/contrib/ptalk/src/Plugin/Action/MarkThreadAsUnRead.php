<?php

namespace Drupal\ptalk\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Mark conversation as unread.
 *
 * @Action(
 *   id = "ptalk_thread_mark_as_unread_action",
 *   label = @Translation("Mark conversation as unread"),
 *   type = "ptalk_thread"
 * )
 */
class MarkThreadAsUnRead extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($thread = NULL) {
    $thread->markThread(PTALK_UNREAD);
    $thread->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\ptalk\ThreadInterface $object */
    $result = $object->access('edit', $account, TRUE)
      ->andIf($object->access('update', $account, TRUE));

    return $return_as_object ? $result : $result->isAllowed();
  }

}
