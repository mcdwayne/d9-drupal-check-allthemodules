<?php
/**
 * Handles interactions with the table of stored payments.
 * @author appels
 */

namespace Drupal\adcoin_payments\Model;
use Drupal\adcoin_payments\Exception\DatabaseException;

class PaymentStorage {

  /*****************************************************************************
   * Payment status
   ****************************************************************************/
   public static $UNPAID           = 0;
   public static $PAID_UNCONFIRMED = 1;
   public static $PAID_CONFIRMED   = 2;
   public static $COMPLETED        = 3;
   public static $TIMED_OUT        = 4;

   /**
    * Get friendly name of the given status.
    *
    * @param int $status Status int. See above.
    *
    * @return string
    */
  public static function getStatusText($status) {
    switch ($status) {
      case self::$UNPAID:
        return t('Unpaid');
      case self::$PAID_UNCONFIRMED:
        return t('Paid (Unconfirmed)');
      case self::$PAID_CONFIRMED:
        return t('Paid & Confirmed');
      case self::$COMPLETED:
        return t('Completed');
      case self::$TIMED_OUT:
        return t('Failed (Timed out)');
    }
   }


  /*****************************************************************************
   * Database operations
   ****************************************************************************/

  /**
   * Store a payment record in the database.
   *
   * @param array $entry Array containing all the record's fields.
   *
   * @return int The number of updated rows.
   *
   * @throws DatabaseException When insertion fails.
   */
  private static function rowInsert(array $entry) {
    $return_value = NULL;
    try {
      $return_value = \Drupal::database()->insert('adcoin_payments')
        ->fields($entry)
        ->execute();
    } catch (\Exception $e) {
      throw new DatabaseException('rowInsert failed: ' . $e->getMessage());
    }
    return $return_value;
  }

  /**
   * Update the data of a payment record in the database.
   *
   * @param array $entry Array containing the record's fields to update.
   *
   * @return int The number of updated rows.
   *
   * @throws DatabaseException When updating fails.
   */
  private static function rowUpdate(array $entry) {
    try {
      $count = \Drupal::database()->update('adcoin_payments')
        ->fields($entry)
        ->condition('payment_id', $entry['payment_id'])
        ->execute();
    }
    catch (\Exception $e) {
      throw new DatabaseException('rowUpdate failed: ' . $e->getMessage());
    }
    return $count;
  }



  /*****************************************************************************
   * Payment record operations
   ****************************************************************************/

  /**
   * Opens a payment record.
   *
   * @param string $payment_id The payment's ID returned by the Wallet API.
   * @param string $created_at When the payment was opened.
   * @param string $name       Customer's name.
   * @param string $email      Customer's email.
   * @param string $phone      Customer's phone number.
   * @param string $postal     Customer's postal- or zipcode.
   * @param string $country    Customer's country.
   * @param int    $amount     Amount to pay in AdCoins.
   *
   * @throws DatabaseException When insertion fails.
   */
  public static function paymentOpen($payment_id, $created_at, $name, $email, $phone, $postal, $country, $amount) {
    self::rowInsert([
      'payment_id' => $payment_id,
      'created_at' => $created_at,
      'name'       => $name,
      'email'      => $email,
      'phone'      => $phone,
      'postal'     => $postal,
      'country'    => $country,
      'amount'     => $amount
    ]);
  }

  /**
   * Updates the status of a payment record in the database.
   *
   * @param string $payment_id The payment's ID given by the Wallet API.
   * @param int    $status     New payment status.
   *
   * @return int The number of updated rows.
   *
   * @throws DatabaseException When updating fails.
   */
  public static function paymentUpdateStatus($payment_id, $status) {
    return self::rowUpdate([
      'payment_id' => $payment_id,
      'status'     => $status
    ]);
  }

  /**
   * Delete the given payment record from the database.
   *
   * @param string $payment_id The payment record's ID.
   */
  public static function paymentDelete($payment_id) {
    try {
      \Drupal::database()->delete('adcoin_payments')
        ->condition('payment_id', $payment_id)
        ->execute();
    } catch (\Exception $e) {
      throw new DatabaseException('paymentDelete failed: ' . $e->getMessage);
    }
  }

  /**
   * Fetches a payment record from the database.
   *
   * @param string $payment_id The payment record's ID.
   *
   * @return array Row data of the payment record if the
   *               record was found.
   */
  public static function paymentFetch($payment_id) {
    $query = \Drupal::database()->select('adcoin_payments', 'pmnts');
    $query->condition('payment_id', $payment_id);
    $query->fields('pmnts', ['payment_id', 'name', 'email', 'phone', 'postal', 'country', 'created_at', 'status', 'amount']);
    $result = $query->execute();
    return $result->fetchAssoc();
  }
}
