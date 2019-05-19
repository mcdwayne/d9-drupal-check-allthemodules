<?php

namespace Drupal\vote_anon\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Symfony\Component\HttpFoundation\Request;
use Drupal\user\Entity\User;

/**
 * Class SingleNodeVoteAjaxController.
 */
class SingleNodeVoteAjaxController extends ControllerBase {

  /**
   * Rendersinglenodevotelinkrenderable.
   *
   * @return string
   *   Return Hello string.
   */
  public function renderSingleNodeVoteLinkRenderable($node, $nojs, Request $request) {
    // Get the session from the request object.
    $session = $request->getSession();
    $session_id = $session->getId();
    // Get UUID.
    $uid = 0;
    $uid = \Drupal::currentUser()->id();
    $user = User::load($uid);
    $uuid = $user->uuid();
    // Determine whether the request is coming from AJAX or not.
    if ($nojs == 'ajax') {
      $config = \Drupal::config('vote_anon.voteconfiguration');
      $cookie = $config->get('voting_cookie');
      $diable_vote_link = $config->get('diable_vote_link');
      // Check if user has already vote for this node.
      $id = \Drupal::database()->select('vote_anon', 'vote_anon')
        ->fields('vote_anon', ['id'])
        ->condition('entity_id', $node)
        ->condition('session_id', $session_id)
        ->execute()->fetchField();
      if (!$id) {
        \Drupal::database()->insert('vote_anon')->fields(
          [
            'entity_type' => 'node',
            'uid' => $uid,
            'uuid' => $uuid,
            'entity_id' => $node,
            'session_id' => $session_id,
            'created' => time(),
          ]
        )->execute();
        $vote_id = \Drupal::database()->select('vote_anon_counts', 'vote')
          ->fields('vote', ['vote_id'])
          ->condition('entity_id', $node)
          ->execute()->fetchField();
        if ($vote_id) {
          \Drupal::database()->update('vote_anon_counts')
            ->expression('count', 'count + 1')
            ->condition('vote_id', $vote_id)
            ->condition('entity_id', $node)
            ->execute();
        }
        else {
          \Drupal::database()->insert('vote_anon_counts')->fields(
            [
              'entity_type' => 'node',
              'count' => 1,
              'entity_id' => $node,
              'last_updated' => time(),
            ]
          )->execute();
        }
        $output = '<div class="ajax-message">' . $this->t("Thank you for vote") . '</div>';
      }
      else {
        $output = '<div class="ajax-message">' . $this->t("You have already submitted") . '</div>';
      }
    }
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand("#votedestinationdiv{$node}", $output));
    // Diable vote link.
    if ($diable_vote_link) {
      $response->addCommand(new InvokeCommand(NULL, 'disableVoteLinks', ["{$node}"]));
    }
    return $response;
  }

}
