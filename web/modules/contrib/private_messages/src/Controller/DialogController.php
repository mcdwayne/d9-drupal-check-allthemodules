<?php

namespace Drupal\private_messages\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\private_messages\Form\BlockUserForm;

/**
 * Class DialogController.
 *
 * @package Drupal\private_messages\Controller
 */
class DialogController extends ControllerBase {

  public function getPageTitle($a = 1) {
    $a = 1;
  }

  /**
   * All dialogs.
   *
   * @return array|null
   */
  public function all() {
    return views_embed_view('dialogs', 'block_all');
  }

  /**
   * Inbox.
   *
   * @return string
   *   Return Inbox please.
   */
  public function inbox() {
    return views_embed_view('dialogs', 'block_inbox');
  }

  /**
   * Sent.
   *
   * @return string
   *   Return Sent please.
   */
  public function sent() {
    return views_embed_view('dialogs', 'block_sent');
  }

  /**
   * Blocked.
   *
   * @return string
   *   Returns blocked users.
   */
  public function blocked() {
    $form = \Drupal::formBuilder()->getForm(BlockUserForm::class);
    $view = views_embed_view('blocked_users', 'block_list');

    return [
      '#theme' => 'blocked_users',
      '#view'  => $view,
      '#form'  => $form,
    ];
  }

}
