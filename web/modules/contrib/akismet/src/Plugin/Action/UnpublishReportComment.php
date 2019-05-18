<?php

namespace Drupal\akismet\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\akismet\Client\FeedbackManager;
use Drupal\akismet\EntityReportAccessManager;

/**
 * Unpublishes a comment and reports to Akismet.
 *
 * @Action(
 *   id = "akismet_comment_unpublish_action",
 *   label = @Translation("Report to Akismet and unpublish"),
 *   type = "comment"
 * )
 */
class UnpublishReportComment extends ActionBase {

  /**
   * {@inheritdoc}
   * @param $comment \Drupal\comment\CommentInterface
   */
  public function execute($comment = NULL) {
    if (empty($comment)) {
      return;
    }
    FeedbackManager::sendFeedback('comment', $comment->id(), 'spam');

    $comment->setPublished(FALSE);
    $comment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\comment\CommentInterface $object */

    $node = $object->getCommentedEntity();
    $form_id = 'comment_' . $node->getEntityTypeId() . '_' . $node->bundle() . '_form';
    $result = EntityReportAccessManager::accessReport($object, $form_id, $account);
    return $return_as_object ? $result : $result->isAllowed();
  }

}
