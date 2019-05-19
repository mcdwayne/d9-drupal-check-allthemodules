<?php

namespace Drupal\social_course\Access;

use Drupal\node\Access\NodeAddAccessCheck;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeTypeInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Determines access to for node add pages.
 *
 * @ingroup node_access
 */
class ContentAccessCheck extends NodeAddAccessCheck {

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account, NodeTypeInterface $node_type = NULL) {
    $forbidden = ['course_article', 'course_section', 'course_video'];

    if (in_array($node_type->id(), $forbidden)) {
      return AccessResult::forbidden();
    }

    return parent::access($account, $node_type);
  }

}
