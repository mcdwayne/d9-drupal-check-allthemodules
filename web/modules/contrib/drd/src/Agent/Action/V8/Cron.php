<?php

namespace Drupal\drd\Agent\Action\V8;

/**
 * Provides a 'Cron' code.
 */
class Cron extends Base {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    \Drupal::getContainer()->get('cron')->run();
    return [];
  }

}
