<?php

namespace Drupal\bestreply\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\comment\CommentInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Controller routines for bestreply routes.
 */
class BestReplyController extends ControllerBase {

  /**
   * Insert or update the marked comment info.
   */
  public function mark(CommentInterface $comment, $js = NULL) {
    $user = \Drupal::currentUser();
    $rt = FALSE;
    $dt = REQUEST_TIME;

    if ($comment->getCommentedEntityTypeId() == 'node') {
      if ($comment->isPublished()) {
        if (BestReplyController::bestreplyIsmarked($comment->getCommentedEntityId())) {
          $action = 'replace';
          $rt = db_query('UPDATE {bestreply} SET cid = :cid, aid = :aid, uid = :uid, dt = :dt  where nid = :nid',
              [
                'cid' => $comment->id(),
                'aid' => $comment->getOwnerId(),
                'uid' => $user->id(),
                'dt' => $dt,
                'nid' => $comment->getCommentedEntityId(),
              ]);
        }
        else {
          $action = 'mark';
          $rt = db_query('INSERT into {bestreply} values( :nid, :cid, :aid, :uid, :dt)',
              [
                'nid' => $comment->getCommentedEntityId(),
                'cid' => $comment->id(),
                'aid' => $comment->getOwnerId(),
                'uid' => $user->id(),
                'dt' => $dt,
              ]);
        }

        if ($js) {
          $status = ($rt) ? TRUE : FALSE;
          print Json::encode([
            'status' => $status,
            'cid' => $comment->id(),
            'action' => $action,
          ]);
          exit;
        }
      }
    }
  }

  /**
   * Return the marked cid (comment id) for the given node id.
   */
  public static function bestreplyIsmarked($nid = NULL) {
    if (!$nid) {
      return FALSE;
    }
    return db_query('SELECT cid FROM {bestreply} WHERE nid = :nid', ['nid' => $nid])->fetchField();
  }

  /**
   * Clear the marked comment info.
   */
  public function clear(CommentInterface $comment, $js = NULL) {
    if (BestReplyController::bestreplyIsmarked($comment->getCommentedEntityId())) {
      $rt = db_query("DELETE FROM {bestreply} WHERE nid = :nid", ['nid' => $comment->getCommentedEntityId()]);
    }
    if ($js) {
      $status = ($rt) ? TRUE : FALSE;
      print Json::encode([
        'status' => $status,
        'cid' => $comment->id(),
        'action' => 'clear',
      ]);
      exit;
    }
  }

  /**
   * List all the best reply data.
   */
  public function replyCommentList() {
    $head = [
    ['data' => 'title'],
    ['data' => 'author', 'field' => 'cname', 'sort' => 'asc'],
    ['data' => 'marked by', 'field' => 'name', 'sort' => 'asc'],
    ['data' => 'when', 'field' => 'dt', 'sort' => 'asc'],
    ];

    $sql = db_select('bestreply', 'b')
      ->fields('b', ['nid', 'cid', 'uid', 'aid', 'dt']);

    $sql->join('node_field_data', 'n', 'n.nid = b.nid');
    $sql->addField('n', 'title');
    $sql->join('comment_field_data', 'c', 'c.cid = b.cid');
    $sql->addField('c', 'name', 'cname');
    $sql->join('users_field_data', 'u', 'u.uid = b.uid');
    $sql->addField('u', 'name');

    $sql = $sql->extend('Drupal\Core\Database\Query\PagerSelectExtender')->extend('Drupal\Core\Database\Query\TableSortExtender')->orderByHeader($head);
    $result = $sql->execute()->fetchAll();

    foreach ($result as $reply) {
      $options = ['fragment' => 'comment-' . $reply->cid];
      $author = !empty($reply->aid) ? Link::fromTextAndUrl($reply->cname, Url::fromUri('entity:user/' . $reply->aid)) : \Drupal::config('user.settings')->get('anonymous');
      $reply_user = !empty($reply->uid) ? Link::fromTextAndUrl($reply->name, Url::fromUri('entity:user/' . $reply->uid)) : \Drupal::config('user.settings')->get('anonymous');
      $rows[] = [
        Link::fromTextAndUrl($reply->title, Url::fromUri('entity:node/' . $reply->nid, $options)),
        $author,
        $reply_user,
        $this->t('@time ago', ['@time' => \Drupal::service('date.formatter')->formatInterval(REQUEST_TIME - $reply->dt)]),
      ];
    }

    if (isset($rows)) {
      // Add the pager.
      $build['content'] = [
        '#theme' => 'table',
        '#header' => $head,
        '#rows' => $rows,
      ];
      $build['pager'] = [
        '#type' => 'pager'
      ];
      return $build;
    }
    else {
      return [
        '#markup' => $this->t('No results to display'),
      ];
    }
  }

}
