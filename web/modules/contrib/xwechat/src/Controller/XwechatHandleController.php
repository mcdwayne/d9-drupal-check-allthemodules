<?php

namespace Drupal\xwechat\Controller;

use Drupal\Core\Controller\ControllerBase;
use Pyramid\Component\WeChat\Request;
use Pyramid\Component\WeChat\Response;
use Pyramid\Component\WeChat\WeChat;

/**
 * Class XwechatHandleController.
 *
 * @package Drupal\xwechat\Controller
 */
class XwechatHandleController extends ControllerBase {

  /**
   * Callback.
   *
   * @return string
   *   Return Hello string.
   */
  public function callback($xwechat_config = Null) {
    $wechat  = new WeChat($xwechat_config);
    $request = Request::createFromGlobals();
    $response = $wechat->handle($request);
    $response->send();
  }

}
