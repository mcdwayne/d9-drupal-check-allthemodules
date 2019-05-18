<?php

namespace Drupal\gopay;

/**
 * Interface GoPayFactoryInterface.
 *
 * @package Drupal\gopay
 */
interface GoPayFactoryInterface {

  /**
   * Creates instance of payer contact.
   *
   * @return \Drupal\gopay\Contact\ContactInterface
   *   Contact object.
   */
  public function createContact();

  /**
   * Creates instance of item.
   *
   * @return \Drupal\gopay\Item\ItemInterface
   *   Item object.
   */
  public function createItem();

  /**
   * Creates instance of standard payment.
   *
   * @return \Drupal\gopay\Payment\PaymentInterface
   *   StandardPayment object.
   */
  public function createStandardPayment();

  /**
   * Gets Payment status.
   *
   * @param int $id
   *   Payment Id.
   *
   * @return \Drupal\gopay\Response\PaymentResponseInterface
   *   Response object.
   */
  public function createResponseStatus($id);

}
