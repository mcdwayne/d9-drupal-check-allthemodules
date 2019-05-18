<?php

namespace Drupal\akismet\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\akismet\Client\FeedbackManager;
use Drupal\akismet\EntityReportAccessManager;

/**
 * Unpublishes a node and reports to Akismet.
 *
 * @Action(
 *   id = "akismet_node_unpublish_action",
 *   label = @Translation("Report to Akismet and unpublish"),
 *   type = "node"
 * )
 */
class UnpublishReportNode extends ActionBase {

  /**
   * {@inheritdoc}
   * @param $node \Drupal\node\NodeInterface
   */
  public function execute($node = NULL) {
    if (empty($node)) {
      return;
    }
    FeedbackManager::sendFeedback('node', $node->id(), 'spam');

    $node->setPublished(FALSE);
    $node->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\node\NodeInterface $object */
    $form_id = 'node_' . $object->bundle() . '_form';
    $result = EntityReportAccessManager::accessReport($object, $form_id, $account);
    return $return_as_object ? $result : $result->isAllowed();
  }

}
