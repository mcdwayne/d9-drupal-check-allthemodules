<?php

namespace Drupal\drd\Agent\Action\V7;

class Session extends Base {


  /**
   * {@inheritdoc}
   */
  public function execute() {
    $account = user_load(1);
    return array(
      'url' => user_pass_reset_url($account) . '/login?destination=/admin',
    );
  }

}
