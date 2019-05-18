<?php

namespace Drupal\anonymous_publishing_cl\Controller;

use Drupal\comment\Entity\Comment;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;

/**
 * Default controller for the anonymous_publishing_cl module.
 */
class AnonymousPublishingController extends ControllerBase {

  public function verifyAnonymousPost() {

    $akey = \Drupal::request()->query->get('akey', NULL);

    if ($akey == NULL) {
      drupal_set_message(t('No activation key present.'));
      return $this->redirect('<front>');
    }

    // Check if the key exists.
    $result = \Drupal::database()
      ->select('anonymous_publishing')
      ->fields('anonymous_publishing')
      ->condition('akey', $akey)
      ->execute()
      ->fetchAssoc();
    
    $nid = $result['nid'];
    $cid = $result['cid'];
    $rkey  = $result['akey'];
    $wish  = $result['alias'];
    $vfied = $result['verified'];
    $email = $result['email'];
    $at = $akey[0];


    if ($akey != $rkey) {
      drupal_set_message(t('Invalid activation key.'), 'error');
      return $this->redirect('<front>');
    }

    if ($vfied) {
      drupal_set_message(t('Stale activation key.'), 'error');
      return $this->redirect('<front>');
    }

    $result = \Drupal::database()
      ->select('anonymous_publishing_emails')
      ->fields('anonymous_publishing_emails')
      ->condition('email', $email)
      ->execute()
      ->fetchAssoc();
    if (!empty($result)) {
      if ($result['blocked']) {
        // Hand-moderate if already blocked.
        $at = 'V';
      }
    } else {
      $ip = \Drupal::request()->getClientIp();
      $now = date('Y-m-d');
      $auid = \Drupal::database()
        ->insert('anonymous_publishing_emails')
        ->fields(array(
          'email' => $email,
          'ipaddress' => $ip,
          'firstseen' => $now,
          'lastseen' => $now,
        ))
        ->execute();
      $aliasopt = \Drupal::config('anonymous_publishing_cl.settings')->get('user_alias');

      if ($aliasopt == 'alias') {
        $alias = 'user' . $auid;
      }
      elseif (!empty($wish)) {
        $alias = $wish;
      }
      else {
        $alias = '';
      }

      \Drupal::database()
        ->update('anonymous_publishing_emails')
        ->fields(['alias' => $alias])
        ->condition('auid', $auid, '=')
        ->execute();
    }

    \Drupal::database()
      ->update('anonymous_publishing')
      ->fields(['verified' => 1])
      ->condition('akey', $akey, '=')
      ->execute();

    $vfymsg = t('Thanks for verifying your e-mail,');
    if ('V' == $at) {
      drupal_set_message($vfymsg . ' ' . t('your content will be published when it has been approved by an administrator.'));
      return $this->redirect('<front>');
    }
    else {
      // Activate (unless comment moderation).
      if ($cid && \Drupal::currentUser()
          ->hasPermission('skip comment approval')
      ) {
        $comment = Comment::load($cid);
        $comment->setPublished(TRUE);
        $comment->save();
        drupal_set_message($vfymsg . ' ' . t('your comment has been published and will appear on the site soon.'));
        $url = $comment->permalink();
        return $this->redirect($url->getRouteName(), $url->getRouteParameters(), $url->getOptions());
      }
      elseif ($cid) {
        drupal_set_message($vfymsg . ' ' . t('your comment will be published when it has been approved by an administrator.'));
        return $this->redirect('<front>');
      }
      else {
        $node = Node::load($nid);
        $node->setPublished(TRUE);
        $node->save();
        drupal_set_message($vfymsg . ' ' . t('your content has been published and will appear on the site soon.'));
        if ($node->access('view')) {
          return $this->redirect($node->toUrl()->getRouteName(), array('node' => $nid));
        }
      }
    }
  }
}
