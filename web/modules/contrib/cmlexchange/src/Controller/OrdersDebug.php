<?php

namespace Drupal\cmlexchange\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * OrdersDebug.
 */
class OrdersDebug extends ControllerBase {

  /**
   * Page.
   */
  public function page() {
    $from = strtotime('now -2 week');
    $orders = \Drupal::service('cmlexchange.orders');
    $xml = $orders->xml($from);
    $msg = "Заказы с " . format_date($from, 'custom');
    drupal_set_message($msg);
    if (\Drupal::moduleHandler()->moduleExists('devel')) {
      dsm($xml);
    }
    else {
      return ['#markup' => $this->t('Missing module `devel`')];
    }
    return ['#markup' => 'ok'];
  }

}
