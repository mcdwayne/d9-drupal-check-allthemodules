<?php

/**
 * @file
 * Contains \Drupal\xwechat_message\Controller\MessageSendController.
 */

namespace Drupal\xwechat_message\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Query;

use Drupal\Core\Url;
use Pyramid\Component\WeChat\WeChat;
use Pyramid\Component\WeChat\Request;
use Pyramid\Component\WeChat\Response;

class MessageSendController extends ControllerBase {
  public function content($xwechat_config) {
    $wechat = new WeChat($xwechat_config);
    $wechat->getAccessToken();

//    $status = $wechat->sendCustomMessage(array('touser'=>'oBbuuw7ATLPQLm2vqcaXcebx09-k','msgtype'=>'text','text'=>array('content'=>'Dear, success!')));
//    $status = $wechat->sendMassMessage(
//      array(
//        'filter'=>array(
//          'is_to_all' => false,
//          'group_id' => 0,
//        ),
//        'msgtype'=>'text',
//        'text'=>array(
//          'content' => t('only English? oh shit！'),
//        )
//      )
//    );

    $status = $wechat->getMaterialList(array('type'=>'news', 'offset' => 0, 'count' => 20));
    
    if($status){
      return array(
        '#type' => 'markup',
        '#markup' => $status['errmsg'],
      );
    }else{
      return array(
        '#type' => 'markup',
        '#markup' => t('发送失败'),
      );
    }
  }
}
