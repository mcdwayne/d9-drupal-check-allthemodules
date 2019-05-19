<?php

/**
 * @file
 * Contains \Drupal\xwechat_message\Controller\MessageListController.
 */

namespace Drupal\xwechat_message\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Query;

class MessageListController extends ControllerBase {
  public function content($xwechat_config) {
    $query = db_select('xwechat_message', 'm');
    $result = $query->fields('m')
               ->condition('wid', $xwechat_config->wid)
               ->orderBy('id', 'DESC')
               ->execute()
               ->fetchAll();

    $header = array(
      'type' => t('Type'),
      'stamp' => t('Timestamp'),
      'image' => t('Image'),
      'openid' => t('OpenID'),
      'msgtype' => t('MsgType'),
      'subtype' => t('LibEvent'),
      'content' => t('Content'),
    );

    $rows = array();
    if ($uids = pyramid_array_column($result, 'openid')) {
      $users = db_select('xwechat_user', 'u')
                ->fields('u')
                ->condition('openid', $uids)
                ->execute()
                ->fetchAllAssoc('openid');
    }

    foreach ($result as $v) {
      $content  = json_decode($v->data, true);
      $username = $v->openid;
      if (!empty($users[$v->openid]->remark)) {
          $username = $users[$v->openid]->remark;
      } elseif (!empty($users[$v->openid]->nickname)) {
          $username = HtmlString::decodeEmoji($users[$v->openid]->nickname);
      }
      if (!empty($users[$v->openid]->headimgurl)) {
          $headimgurl = $users[$v->openid]->headimgurl;
      } else {
          $headimgurl = '';
      }
      $body = \Drupal::moduleHandler()->invokeAll('xwechat_messageshown', $args = array($content, $v->subtype, $wid));
      $rows[$v->id] = array(
        'type' => $v->type,
        'stamp' => format_date($v->timestamp, 'medium', 'Y-m-d H:i:s'),
        'image' => $headimgurl ? "<img style='max-width:48px' src='{$headimgurl}' />" : '',
        'openid' => '<strong>' . $username . '</strong>',
        'msgtype' => $v->msgtype,
        'subtype' => $v->subtype,
        'content' => $body[0],
      );
    }

    $table = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    );
    $markup = drupal_render($table);

    return array(
        '#type' => 'markup',
        '#markup' => $markup,
    );
  }
}
