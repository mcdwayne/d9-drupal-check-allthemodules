<?php
/**
 * Created by PhpStorm.
 * User: gue
 * Date: 30.09.16
 * Time: 10:23
 */

namespace Drupal\nodeletter;


class NodeletterSendException extends \Exception {

  const CODE_UNDEFINED_ERROR = 0;
  const CODE_SERVICE_CONNECTION_ERROR = 1;
  const CODE_SERVICE_API_ERROR = 2;
  const CODE_BAD_CONFIG = 3;
  const CODE_INVALID_CONTENT = 4;
  const CODE_BAD_RECIPIENTS = 5;
  const CODE_SERVICE_ERROR = 6;

  public static function describe( $code, $translate = TRUE ) {
    switch($code) {
      case 1:
        $msg = "Could not connect to service";
        break;
      case 2:
        $msg = "Service API error";
        break;
      case 3:
        $msg = "Sending's configuration not accepted";
        break;
      case 4:
        $msg = "Sending's content not accepted";
        break;
      case 5:
        $msg = "Recipient configuration not accepted";
        break;
      case 6:
        $msg = "Service error";
        break;
      default:
        $msg = "Undefined error";
        break;
    }

    if ($translate) {
      return t($msg);
    } else {
      return $msg;
    }
  }

}
