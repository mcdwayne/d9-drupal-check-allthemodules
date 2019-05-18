<?php

namespace Drupal\drd\Agent\Action\V8;

use Drupal\user\Entity\User;

/**
 * Provides a 'Session' code.
 */
class Session extends Base {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    /** @var \Drupal\user\UserInterface $account */
    $account = User::load(1);
    return [
      'url' => user_pass_reset_url($account) . '/login?destination=/admin',
    ];
  }

}
