<?php

namespace Drupal\drd_remote;

/**
 * Class Setup.
 *
 * @package Drupal\drd_remote
 */
class Setup {
  /**
   * @inheritDoc
   */
  public function __construct() {
  }

  public function execute(&$values) {
    $config = \Drupal::configFactory()->getEditable('drd_remote.settings');

    $values = strtr($values, array('-' => '+', '_' => '/'));
    $values = unserialize(base64_decode($values));
    $authorised = $config->get('authorised');

    $values['timestamp'] = REQUEST_TIME;
    $values['ip'] = \Drupal::request()->getClientIp();
    $authorised[$values['uuid']] = $values;

    $config->set('authorised', $authorised)->save(TRUE);
  }

}
