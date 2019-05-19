<?php


/**
 * @file
 * Contains Drupal\whiteboard\Controller\WhiteboardController
 */

namespace Drupal\whiteboard\Controller;

use Drupal\whiteboard\Whiteboard;

/**
 * Controller routines for whiteboards.
 */
class WhiteboardController {

  /**
   * Posted whiteboard marks are handled here.
   */
  public function postMarks(Whiteboard $whiteboard) {
    $user = \Drupal::currentUser();
    // Check for permission
    if(!$user->hasPermission('write any whiteboard')) {
      return FALSE;
    }
    $config = \Drupal::config('whiteboard.settings');
    if (strlen($_POST['marks']) > $config->get('size', 0)) {
      return FALSE;
    }
    $marks = array(
      'wbid' => $whiteboard->wbid,
      'title' => $this->title,
      'uid' => $this->uid,
      'marks' => $_POST['marks'],
      'format' => $this->format ? $this->format : filter_fallback_format(),
    );
    return $whiteboard->saveMarks($marks);
  }
}
