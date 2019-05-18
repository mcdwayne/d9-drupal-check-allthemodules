<?php

namespace Drupal\forum_access\ForumAccess;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\taxonomy\TermInterface;

/**
 * Access checks for forum.
 */
class Access {

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    module_load_include('inc', 'forum_access', 'includes/forum_access.common');
  }

  /**
   * Access check for forum index page.
   */
  public function forumIndex(AccountInterface $account) {
    $view_access = forum_access_forum_check_view($account);
    if (!empty($view_access)) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

  /**
   * Access check for specific forum page.
   */
  public function forumPage(AccountInterface $account, TermInterface $taxonomy_term) {
    $view_access = forum_access_forum_check_view($account, $taxonomy_term->id());
    if (!empty($view_access)) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

  /**
   * Access for comment reply according to the taxonomy term of forum.
   */
  public function commentReply(EntityInterface $entity, $field_name, $pid = NULL) {
    if ($entity->bundle() != 'forum') {
      return AccessResult::allowed();
    }
    // Forbid if user has no access to reply.
    if (!forum_access_entity_access_by_tid('create', $entity)) {
      return AccessResult::forbidden();
    }
    return AccessResult::allowed();
  }

}
