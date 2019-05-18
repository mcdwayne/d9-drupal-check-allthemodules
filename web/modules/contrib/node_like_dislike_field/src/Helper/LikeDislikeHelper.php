<?php

namespace Drupal\node_like_dislike_field\Helper;

/**
 * This is  class to avoid multiple instantiation.
 */
class LikeDislikeHelper {

  /**
   * Keep class object.
   *
   * @var object
   */
  public static $likedislike = NULL;

  /**
   * Get class instance using this function.
   *
   * @return LikeDislikeHelper
   *   Returns a self ofject reference.
   */
  public static function getInstance() {
    if (!self::$likedislike) {
      self::$likedislike = new LikeDislikeHelper();
    }
    return self::$likedislike;
  }

  /**
   * Function returns the Ip address.
   *
   * @return ipaddress
   *   Returns ipadress of a server.
   */
  public function getClientIp() {

    $ipaddress = '';
    if (getenv('HTTP_CLIENT_IP')) {
      $ipaddress = getenv('HTTP_CLIENT_IP');
    }
    elseif (getenv('HTTP_X_FORWARDED_FOR')) {
      $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
    }
    elseif (getenv('HTTP_X_FORWARDED')) {
      $ipaddress = getenv('HTTP_X_FORWARDED');
    }
    elseif (getenv('HTTP_FORWARDED_FOR')) {
      $ipaddress = getenv('HTTP_FORWARDED_FOR');
    }
    elseif (getenv('HTTP_FORWARDED')) {
      $ipaddress = getenv('HTTP_FORWARDED');
    }
    elseif (getenv('REMOTE_ADDR')) {
      $ipaddress = getenv('REMOTE_ADDR');
    }
    else {
      $ipaddress = 'UNKNOWN';
    }
    return $ipaddress;
  }

}
