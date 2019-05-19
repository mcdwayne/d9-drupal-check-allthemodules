<?php

/**
 * @file
 * Contains \Drupal\xwechat_material\Controller\MaterialAddController.
 */

namespace Drupal\xwechat_material\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Query;

use Drupal\Core\Url;
use Pyramid\Component\WeChat\WeChat;
use Pyramid\Component\WeChat\Request;
use Pyramid\Component\WeChat\Response;

class MaterialAddController extends ControllerBase {

  /**
   * Add materials.
   */
  public function addMaterial($xwechat_config = NULL) {
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
//       $status = $wechat->getMaterialCount();
//      $status = $wechat->getMaterialList(array('type'=>'news', 'offset' => 0, 'count' => 20));
      $status = $wechat->uploadForeverMedia(array('articles'=> array(
        array(
          'title' => 'pangdou',
          'thumb_media_id' => 'kaPMn-dhtlIHJQuiufXtcpioks3ZyQOv4AkmOWdRBVM',
          'author' => 'lanyulu',
          'digest' => 'sunmerssy',
          'show_cover_pic' => 0,
          'content' => 'i am content',
          'content_source_url' => 'http://www.baidu.com',
        )        
      )));
    
    if($status){
      print_r($status);die();
    }else{
      return array(
        '#type' => 'markup',
        '#markup' => t('发送失败'),
      );
    }
  }
}
