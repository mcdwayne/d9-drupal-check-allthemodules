<?php

/**
 * @file
 * Definition of og_sm_comment_handler_field_comment_link_delete.
 */

/**
 * Field handler to present a link to delete a comment.
 *
 * We need to overwrite access checks for this handler since it checks on
 * 'administer comments' to delete comments.
 */
// @codingStandardsIgnoreStart
class og_sm_comment_handler_field_comment_link_delete extends views_handler_field_comment_link_delete {

  /**
   * {@inheritdoc}
   */
  public function access() {
    if (!isset($this->definition['access callback'])) {
      return TRUE;
    }
    if (!function_exists($this->definition['access callback'])) {
      return TRUE;
    }
    if (isset($this->definition['access arguments']) && is_array($this->definition['access arguments'])) {
      return call_user_func_array($this->definition['access callback'], $this->definition['access arguments']);
    }
    return $this->definition['access callback']();
  }

  /**
   * {@inheritdoc}
   */
  public function render_link($date, $values) {
    $cid = $this->get_value($values, 'cid');
    if (!og_sm_comment_access('delete', $cid)) {
      return;
    }
    return parent::render_link($date, $values);
  }

}
// @codingStandardsIgnoreEnd
