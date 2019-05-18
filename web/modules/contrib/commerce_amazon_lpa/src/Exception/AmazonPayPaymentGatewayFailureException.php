<?php

namespace Drupal\commerce_amazon_lpa\Exception;

use Drupal\commerce_payment\Exception\PaymentGatewayException;

/**
 * Exception used for Amazon Pay failures.
 */
class AmazonPayPaymentGatewayFailureException extends PaymentGatewayException {}
