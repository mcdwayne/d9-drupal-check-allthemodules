<?php

namespace Drupal\user_discount_code\Controller;

use Drupal\Core\Controller\ControllerBase;

class CodePageController extends ControllerBase {

  /**
   * controller discount page
   * @return array to render page
   */
  public function pageController(){
    $config = \Drupal::config('userDiscountCode.settings');

    $token_service = \Drupal::token();
    $message = $token_service->replace($config->get('message'));

    return [
      '#theme' => ['discountPage'],
      '#titileDC' => $config->get('title'),
      '#message' => $message,
    ];
  }
}