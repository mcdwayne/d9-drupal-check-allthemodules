<?php

namespace Drupal\gopay;

use GoPay\Definition\Response\PaymentStatus;
use GoPay\Definition\Response\PaymentSubStatus;

/**
 * Class GoPayState.
 *
 * @package Drupal\gopay
 */
class GoPayState {

  /**
   * Return description for given state.
   */
  public static function getStateDescription($state) {
    switch ($state) {
      case PaymentStatus::CREATED:
        return 'Payment created';

      case PaymentStatus::PAYMENT_METHOD_CHOSEN:
        return 'Payment method chosen';

      case PaymentStatus::PAID:
        return 'Payment paid';

      case PaymentStatus::AUTHORIZED:
        return 'Payment pre-authorized';

      case PaymentStatus::CANCELED:
        return 'Payment canceled';

      case PaymentStatus::TIMEOUTED:
        return 'Payment timeouted';

      case PaymentStatus::REFUNDED:
        return 'Payment refunded';

      case PaymentStatus::PARTIALLY_REFUNDED:
        return 'Payment partially refunded';

      default:
        return '';
    }
  }

  /**
   * Return description for given sub state.
   */
  public static function getSubStateDescription($sub_state) {
    switch ($sub_state) {
      case PaymentSubStatus::_101:
        return 'Payment pending. We are waiting for the online payment to be made.';

      case PaymentSubStatus::_102:
        return 'Payment pending. We are waiting for the offline payment to be made.';

      case PaymentSubStatus::_3001:
        return 'Bank payment confirmed by letter of advice.';

      case PaymentSubStatus::_3002:
        return 'Bank payment confirmed by statement.';

      case PaymentSubStatus::_3003:
        return 'Bank payment not authorised.';

      case PaymentSubStatus::_5002:
        return 'Payment declined by the customer’s bank authorization centre. The payment card limit had been reached.';

      case PaymentSubStatus::_5003:
        return 'Payment declined by the customer’s bank authorization centre. There are some issues at the card issuer side.';

      case PaymentSubStatus::_5004:
        return 'Payment declined by the customer’s bank authorization centre. Issues at the card issuer side.';

      case PaymentSubStatus::_5005:
        return 'Payment declined by the customer’s bank authorization centre. Payment card blocked.';

      case PaymentSubStatus::_5006:
        return 'Payment declined by the customer’s bank authorization centre. Insufficient funds at the payment card.';

      case PaymentSubStatus::_5007:
        return 'Payment declined by the customer’s bank authorization centre. The payment card is expired.';

      case PaymentSubStatus::_5008:
        return 'Payment declined by the customer’s bank authorization centre. The CVV/CVC code had been declined.';

      case PaymentSubStatus::_5009:
        return 'Payment declined in the 3D Secure system of the customer’s bank.';

      case PaymentSubStatus::_5010:
        return 'Payment declined by the customer’s bank authorization centre. There are some issues with the payment card.';

      case PaymentSubStatus::_5011:
        return 'Payment declined by the customer’s bank authorization centre. There are some issues with the payment card account.';

      case PaymentSubStatus::_5012:
        return 'Payment declined by the customer’s bank authorization centre. There are some technical issues in the customer’s bank authorization centre.';

      case PaymentSubStatus::_5013:
        return 'Payment declined by the customer’s bank authorization centre. The customer entered an incorrect card number.';

      case PaymentSubStatus::_5014:
        return 'Payment declined by the customer’s bank authorization centre. There are some issues with the payment card.';

      case PaymentSubStatus::_5015:
        return 'Payment declined in the 3D Secure system of the customer’s bank.';

      case PaymentSubStatus::_5016:
        return 'Payment declined by the customer’s bank authorization centre. The customer’s card had not been authorized to make the payment.';

      case PaymentSubStatus::_5017:
        return 'Payment declined in the 3D Secure system of the customer’s bank.';

      case PaymentSubStatus::_5018:
        return 'Payment declined in the 3D Secure system of the customer’s bank.';

      case PaymentSubStatus::_5019:
        return 'Payment declined in the 3D Secure system of the customer’s bank.';

      case PaymentSubStatus::_5021:
        return 'Payment declined by the customer’s bank authorization centre. The card limits had been exceeded.';

      case PaymentSubStatus::_5022:
        return 'A technical issue occured in the customer’s bank authorization centre.';

      case PaymentSubStatus::_5023:
        return 'Payment not made.';

      case PaymentSubStatus::_5024:
        return 'Payment not made. Customer did not enter the payment credentials in the time limit at the payment gateway.';

      case PaymentSubStatus::_5025:
        return 'Payment not made. The specific reason is to be reported to the customer.';

      case PaymentSubStatus::_5026:
        return 'Payment not made. The total credited amounts exceeded the amount paid.';

      case PaymentSubStatus::_5027:
        return 'Payment not made. The user is not authorized to undertake the operation.';

      case PaymentSubStatus::_5028:
        return 'Payment not made. The amount due exceeded the amount authorized.';

      case PaymentSubStatus::_5029:
        return 'Payment has not been made yet.';

      case PaymentSubStatus::_5030:
        return 'Payment not made. There were several attempts to settle the payment.';

      case PaymentSubStatus::_5031:
        return 'A technical issue occurred in the bank while processing the payment.';

      case PaymentSubStatus::_5033:
        return 'SMS failed to be received.';

      case PaymentSubStatus::_5035:
        return 'Card issued in a region where the card payments are not supported.';

      case PaymentSubStatus::_5036:
        return 'Payment declined by the customer’s bank authorization centre. There are some issues with the payment card account.';

      case PaymentSubStatus::_5037:
        return 'Cardholder cancelled the payment.';

      case PaymentSubStatus::_5038:
        return 'Payment not made.';

      case PaymentSubStatus::_5039:
        return 'Payment declined by the customer’s bank authorization centre. The payment card is blocked.';

      case PaymentSubStatus::_5042:
        return 'Bank transfer declined.';

      case PaymentSubStatus::_5043:
        return 'Payment cancelled by user.';

      case PaymentSubStatus::_5044:
        return 'SMS has been sent. It has not been delivered yet.';

      case PaymentSubStatus::_5045:
        return 'Payment received. Payment is to be credited after it has been processed in the Bitcoin system.';

      case PaymentSubStatus::_5046:
        return 'A full amount of payment not made.';

      case PaymentSubStatus::_5047:
        return 'Payment made after due date.';

      case PaymentSubStatus::_6502:
        return 'Payment declined in the 3D Secure system of the customer’s bank.';

      case PaymentSubStatus::_6504:
        return 'Payment declined in the 3D Secure system of the customer’s bank.';

      default:
        return '';
    }
  }

}
