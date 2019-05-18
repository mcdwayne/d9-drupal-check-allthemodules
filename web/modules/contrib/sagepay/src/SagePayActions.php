<?php

namespace Drupal\uc_sagepay;

use Drupal\uc_order\OrderInterface;

/**
 * Defines SagePayActions Class.
 */
class SagePayActions {

  /**
   * Attempt to parse a SagePay errors  into something useful for the customer.
   *
   * Based on the most expensive item.
   *
   * @param string $message
   *   String message.
   *
   * @return string
   *   Translatable markup.
   */
  public static function parseError($message) {
    switch (intval(substr($message, 0, 4))) {
      case 2000:
        // Not authorised.
        return t(
          'The transaction was not authorised by your bank. Please check your
          details and try again, or try with a different card.'
        );

      case 2002:
      case 2003:
        // Authorisation timed out.
        // Server error at Sage Pay.
        return t(
          'Your payment could not be processed at the moment. Please try again
          later.'
        );

      case 3048:
      case 4021:
      case 5011:
        // Invalid card length.
        // Card range not supported.
        // Card number invalid.
        return t('The card number is not valid. Please check and try again.');

      case 3078:
        // Email invalid.
        return t(
          'The email address supplied is not valid. Please check and try again.'
        );

      case 3090:
      case 5055:
        // Postcode blank.
        // Postcode invalid.
        return t('The postal code is not valid. Please check and try again.');

      case 4022:
        // Card type does not match.
        return t(
          'The selected card type does not match the card number. Please check
          and try again.'
        );

      case 4023:
        // Issue number invalid.
        return t(
          'The card issue number is not valid. Please check and try again.'
        );

      case 4027:
        // 3D Secure failed.
        return t(
          'The Verified by Visa or MasterCard SecureCode password was incorrect.
          Please try again.'
        );

      case 5036:
        // Transaction not found.
        return t('The transaction timed out. Please try again.');

      case 5038:
      case 5045:
        // Delivery phone invalid.
        // Billing phone invalid.
        return t(
          'The telephone number is not valid. Please check and try again.'
        );

      case 4008:
        // Currency format invalid.
        return t(
          'The Currency is not supported on this account. Please try again or
          contact the site administrator.'
        );
    }

    return FALSE;
  }

  /**
   * Create a Sage Pay "Description" string from an order.
   *
   * Based on the most expensive item.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   Order Object.
   *
   * @return string
   *   description value.
   */
  public static function sagepayDescription(OrderInterface $order) {
    $description = t('Your order');
    $max = -1;
    foreach ($order->products as $product) {
      if (((int) $product->price->value * (int) $product->qty->value) > $max) {
        $description = substr($product->title->value, 0, 100);
        $max = (int) $product->price->value * (int) $product->qty->value;
      }
    }

    if (count($order->products) > 1) {
      $appendix = ' [etc.] - ' . count($order->products) . ' products';
      $description = substr(
        $description,
        0,
        100 - strlen($appendix)
      ) . $appendix;
    }

    return $description;
  }

  /**
   * Convert card type string to a Sage Pay CardType identifier.
   *
   * @param string $type
   *   String type.
   *
   * @return string
   *   Replace value.
   */
  public static function parseCardType($type) {
    $searches = [
      // Visa Delta needs to take precedence in this list over ordinary Visa.
      '`.*?delta.*`i' => 'DELTA',
      // Visa Electron needs to take precedence in this list over ordinary Visa.
      '`.*?electron.*`i' => 'UKE',
      '`.*?visa.*`i' => 'VISA',
      '`(?:debit).*master[ ]*card.*`i' => 'MCDEBIT',
      '`master[ ]*card.*(?:debit)`i' => 'MCDEBIT',
      '`.*?master[ ]*card.*`i' => 'MC',
      '`.*?(maestro|switch).*`i' => 'MAESTRO',
      '`.*?(amex|american[ ]*express).*`i' => 'AMEX',
      '`.*?diner.*`i' => 'DC',
      '`.*?jcb.*`i' => 'JCB',
    ];

    foreach ($searches as $pattern => $replace) {
      if (preg_match($pattern, $type)) {
        return $replace;
      }
    }
  }

  /**
   * SagePay basket.
   *
   * @param object $order
   *   Order object.
   * @param string $amount
   *   A string amount.
   *
   * @return string
   *   String value.
   */
  public static function sagepayBasket($order, $amount) {
    // The Basket field is <=7,500 characters.
    // The first line is just the total of lines in the basket, followed by a
    // colon.
    // The final line should be shipping, tax, etc., and any items that can't
    // be included in under 7,500 chars.
    // The final line has no final colon.
    $basket['strlen'] = 0;
    $basket['subtotal'] = 0;

    // The number of lines of items, inc. the extra one for shipping, tax and
    // any items that don't fit in under 7,500 chars.
    $basket['lines'] = count($order->products) + 1;

    foreach ($order->products as $product) {
      $item_total = (int) $product->price->value * (int) $product->qty->value;
      $basket['amounts'][] = $item_total;
      $basket['items'][] = str_replace(
        ':',
        ' - ',
        $product->title->value
      ) . ':' . $product->qty->value
        . ':::' . number_format((int) $product->price->value, 2, '.', '')
        . ':' . number_format($item_total, 2, '.', '')
        . ':';

      $basket['strlen'] += strlen(end($basket['items']));
      $basket['subtotal'] += $item_total;
    }

    // At this stage this will probably just be tax & shipping.
    $basket['finalLine']['amount'] = $amount - $basket['subtotal'];
    $basket['finalLine']['text'] = '[Other items, shipping and taxes]:::::';

    // The final line has no final colon, but we need one to complete the first
    // line.
    while (strlen($basket['lines'] . ':') + $basket['strlen'] > (7500 - strlen($basket['finalLine']['text'] . $basket['finalLine']['amount']))) {

      $basket['strlen'] -= strlen(array_pop($basket['items']));
      $basket['lines']--;
      $basket['finalLine']['amount'] += array_pop($basket['amounts']);
    }

    $return = $basket['lines'] . ':' . implode($basket['items'], '');
    $return .= $basket['finalLine']['text'] . $basket['finalLine']['amount'];

    return $return;
  }

  /**
   * Copy of drupal valid_email_address() implementation with a twist.
   *
   * Sage Pay want FQDN on the domain part.
   *
   * Verify the syntax of the given e-mail address.
   *
   * Empty e-mail addresses are allowed. See RFC 2822 for details.
   *
   * @param string $mail
   *   A string containing an e-mail address.
   *
   * @return bool
   *   TRUE if the address is in a valid format.
   */
  public static function sagepayValidEmail($mail) {
    $user = '[a-zA-Z0-9_\-\.\+\^!#\$%&*+\/\=\?\`\|\{\}~\']+';
    $domain = '(?:(?:[a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])(\..*))+';
    $ipv4 = '[0-9]{1,3}(\.[0-9]{1,3}){3}';
    $ipv6 = '[0-9a-fA-F]{1,4}(\:[0-9a-fA-F]{1,4}){7}';

    return preg_match("/^$user@($domain|(\[($ipv4|$ipv6)\]))$/", $mail);
  }

}
