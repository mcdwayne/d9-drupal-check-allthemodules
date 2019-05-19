<?php

namespace Drupal\xwechat\Controller;

use Drupal\Core\Controller\ControllerBase;
use Pyramid\Component\WeChat\Request;
use Pyramid\Component\WeChat\Response;
use Pyramid\Component\WeChat\WeChatCorp;

/**
 * Class XwechatcorpHandleController.
 *
 * @package Drupal\xwechat\Controller
 */
class XwechatcorpHandleController extends ControllerBase {

  /**
   * Callback.
   *
   * @return string
   *   Return Hello string.
   */
  public function callback($xwechat_config = Null) {
    $wechat  = new WeChatCorp($xwechat_config);
    $request = Request::createFromGlobals();
    $response = $wechat->handle($request);
    $response->send();
  }

}
