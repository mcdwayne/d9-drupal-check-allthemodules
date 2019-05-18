<?php

namespace Drupal\node_paypal_payment;

/**
 * Provides the object of the payment history.
 */
class NPPObject {

  /**
   * Get all payments of a user.
   */
  public static function getAll($uid = NULL) {
    $query = db_select('npp_payments', 'p')->fields('p');
    if ($uid) {
      $query->condition('p.uid', $uid);
    }
    $payment = $query->execute()->fetchAll();

    return $payment;
  }

  /**
   * Get a single payment.
   */
  public static function get($id) {
    $payment = db_select('npp_payments', 'p')->fields('p', ['id', 'entity_id'])->condition('p.id', $id)->execute()->fetchObject();

    return $payment;
  }

  /**
   * Check for payment existance.
   */
  public static function exists($id) {
    $result = db_query('SELECT 1 FROM {npp_payments} WHERE id = :id', [':id' => $id])->fetchField();
    return (bool) $result;
  }

}
