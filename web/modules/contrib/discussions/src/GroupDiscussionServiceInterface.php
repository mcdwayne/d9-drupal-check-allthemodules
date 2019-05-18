<?php

namespace Drupal\discussions;

/**
 * Interface for the group discussion service.
 */
interface GroupDiscussionServiceInterface {

  /**
   * Gets a discussion only if part of the given group.
   *
   * @param int $group_id
   *   The ID of the group the discussion is part of.
   * @param int $discussion_id
   *   The ID of the discussion to load.
   *
   * @return \Drupal\discussions\Entity\Discussion
   *   The discussion.
   */
  public function getGroupDiscussion($group_id, $discussion_id);

  /**
   * Gets the group a given discussion is part of.
   *
   * Assumes the discussion only exists in one group.
   *
   * @param int $discussion_id
   *   The ID of the discussion.
   *
   * @return \Drupal\group\Entity\Group
   *   The discussion group.
   */
  public function getDiscussionGroup($discussion_id);

  /**
   * Adds a new comment to a discussion.
   *
   * @param int $discussion_id
   *   The ID of the discussion.
   * @param int $parent_comment_id
   *   The ID of the parent comment, if this is a reply comment.
   * @param int $user_id
   *   The ID of the user ID of the comment author.
   * @param string $comment_body
   *   The comment text.
   * @param array $files
   *   Array of files created in the file system via file_save_data().
   *
   * @return bool
   *   TRUE if comment added, FALSE otherwise.
   */
  public function addComment($discussion_id, $parent_comment_id, $user_id, $comment_body, array $files = []);

}
