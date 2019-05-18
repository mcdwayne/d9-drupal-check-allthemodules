<?php

namespace Drupal\freshmail\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class FreshmailController.
 *
 * @package Drupal\freshmail\Controller
 */
class FreshmailController extends ControllerBase {

  protected $config;

  /**
   * FreshmailController constructor.
   */
  public function __construct() {
    $this->config = $this->config('freshmail.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function addSubscriber($email, $hash_list = '') {

    if (empty($hash_list)) {
      $hash_list = $this->config->get('freshmail_list_id');
    }
    $method = 'subscriber/add/';
    $options = array(
      'email' => $email,
      'list' => $hash_list,
    );

    $request = new FreshmailRestController();
    $request->doRequest($method, $options);
    return $request->getResponse();
  }

}
