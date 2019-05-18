<?php

namespace Drupal\pagarme\Controller;
use Drupal\Core\Controller\ControllerBase;
use Drupal\pagarme\Pagarme\PagarmePostback;

/**
 * Class PagarmePostbackPageController.
 *
 * @package Drupal\pagarme\Controller
 */
class PagarmePostbackPageController extends ControllerBase {

  public static function notificationProcess() {
    try {
      $post = $_POST;
      $pagarmePostback = PagarmePostback::createData($post);
      $pagarmePostback->processPagarmeData();
    }
    catch (Exception $e) {
      print $e->getMessage();
    }
    exit();
  }
}
