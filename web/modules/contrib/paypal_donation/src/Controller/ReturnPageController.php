<?php

namespace Drupal\paypal_donation\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class ReturnPageController.
 *
 * @package Drupal\paypal_donation\Controller
 */
class ReturnPageController extends ControllerBase {

  /**
   * Page which shows if donation was successful.
   *
   * @return string
   *   Returns HTML string.
   */
  public function success() {
    return [
      '#type' => 'markup',
      '#markup' => $this->config('paypal_donation.settings')->get('success_text'),
    ];
  }

  /**
   * Page which shows if donation wasn't successful.
   *
   * @return string
   *   Returns HTML string
   */
  public function fail() {
    return [
      '#type' => 'markup',
      '#markup' => $this->config('paypal_donation.settings')->get('fail_text'),
    ];
  }

}
