<?php

namespace Drupal\comment_approver;

/**
 * Interface CommentTesterInterface.
 */
interface CommentTesterInterface {
  /**
   * Bypass the comment approver.
   */
  const DEFAULT = 0;
  /**
   * Work as comment approver.
   */
  const APPROVER = 1;
  /**
   * Work as comment blocker.
   */
  const BLOCKER = 2;

}
