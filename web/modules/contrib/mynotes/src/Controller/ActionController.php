<?php

namespace Drupal\mynotes\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class ActionController.
 *
 * @package Drupal\mynotes\Controller
 */
class ActionController extends ControllerBase {

  /**
   * Update node fields.
   *
   * Performs actions: Add star/Remove star and Archive/Unarchive.
   */
  public function perform($fieldname, $nid) {
    $node = Node::load($nid);
    $fieldname = 'field_' . $fieldname;
    $current_field_value = $node->{$fieldname}->value;
    $node->{$fieldname}->value = !$current_field_value;
    $node->save();

    $previous_url = \Drupal::request()->server->get('HTTP_REFERER');
    if (!$previous_url) {
      $previous_url = '/notes';
    }
    return new RedirectResponse($previous_url);
  }

}
