<?php

/**
 * @file
 * Theme file stub for tmgmt.
 */

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Render\Element;

/**
 * Theme function to display comments thread.
 *
 * @param array $variables
 *   - thread: Comments thread received from gengo service.
 *
 * @return string
 *   HTML output.
 */
function theme_tmgmt_mygengo_comments_thread($variables) {
  $thread = $variables['thread'];
  $gengo_job_id = $variables['gengo_job_id'];
  if (empty($thread) || !is_array($thread)) {
    return '';
  }

  $comments = array();
  $i = 0;

  foreach ($thread as $comment) {
    $zebra = ++$i % 2 == 0 ? 'even' : 'odd';

    $attributes = new Attribute(array('class' => array('gengo-comment-body', $zebra)));

    $comments[] = array('#markup' => '<div class="gengo-comment-meta">' .
      t('At %time by %author:', array('%time' => format_date($comment['ctime']), '%author' => $comment['author'])) .
      '</div><div ' . $attributes . '>' . SafeMarkup::checkPlain($comment['body']) . '</div>');
  }

  $list = ['#theme' => 'item_list', '#items' => $comments];

  return \Drupal::service('renderer')->render($list) . '<div class="comments-list-closing" id="' . $gengo_job_id . '-comments-list-closing"></div>';
}
