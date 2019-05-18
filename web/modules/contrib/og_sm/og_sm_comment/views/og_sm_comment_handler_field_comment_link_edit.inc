<?php

/**
 * @file
 * Definition of og_sm_comment_handler_field_comment_link_edit.
 */

/**
 * Field handler to present a link to edit a comment.
 *
 * We need to overwrite access checks for this handler since it checks on
 * 'administer comments' to edit comments.
 */
// @codingStandardsIgnoreStart
class og_sm_comment_handler_field_comment_link_edit extends views_handler_field_comment_link_edit {

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
  public function render_link($data, $values) {
    parent::render_link($data, $values);
    // ensure user has access to edit this comment.
    $comment = $this->get_value($values);
    if (!og_sm_comment_access('edit', $comment)) {
      return;
    }

    $text = !empty($this->options['text']) ? $this->options['text'] : t('edit');
    unset($this->options['alter']['fragment']);

    if (!empty($this->options['destination'])) {
      $this->options['alter']['query'] = drupal_get_destination();
    }

    $this->options['alter']['path'] = "comment/" . $comment->cid . "/edit";

    return $text;
  }

}
// @codingStandardsIgnoreEnd
