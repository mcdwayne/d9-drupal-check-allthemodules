<?php

namespace Drupal\revechat\Controller;

/**
 * RevechatController.
 */
class RevechatController {

  /**
   * Generate an example page.
   */
  public function demo() {
    $build['#attached']['library'][] = 'revechat/revechat.revechat_admin_js';
    return $build;
  }

}
