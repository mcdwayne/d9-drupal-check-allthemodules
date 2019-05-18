<?php
 /**
 * Copyright © 2018 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 */

namespace Drupal\commerce_multisafepay\Exceptions;

use Drupal\commerce_payment\Exception\PaymentGatewayException;

class ExceptionHelper
{
    /**
     * Sends a paymentGateway Exception and add a error to the log
     *
     * @param      $errorInfo
     * @param null $errorCode
     */
    public function PaymentGatewayException($errorInfo, $errorCode = null)
    {
        //if code exist
        if(isset($errorCode)){
            (string)$errorCode .= " : ";
        }

        $message = "{$errorCode}{$errorInfo}";
        \Drupal::messenger()->addError($message);
        throw new PaymentGatewayException($message);
    }
}