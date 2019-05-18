<?php

namespace Drupal\ipquery;

class BaseService {

  /**
   * Return if IPv6 is supported.
   *
   * @return bool
   *   TRUE if IPV6 is supported; FALSE otherwise.
   */
  public function isIpv6Supported() {
    return PHP_INT_SIZE >= 8 && function_exists('bcadd');
  }

  /**
   * Return the IPv6 address as two 64 bit numbers.
   *
   * @param string $ip
   *   The IPv6 address.
   *
   * @return array
   *   [0] The left/low/most significant 64 bits as a decimal number.
   *   [1] The right/high/least significant 64 bits as a decimal number.
   */
  function ipToLong($ip) {
    $parts = unpack('N*', inet_pton($ip));

    $left = bcadd($parts[2], bcmul($parts[1], '4294967296'));
    $right = bcadd($parts[4], bcmul($parts[3], '4294967296'));

    return [$left, $right];
  }

  /**
   * Return the decimal string as two 64 bit numbers.
   *
   * @param string $number
   *   The decimal number as a string.
   *
   * @return array
   *   [0] The left/most significant 64 bits as a decimal number in a string.
   *   [1] The right/least significant 64 bits as a decimal number in a string.
   */
  public function numberToLong($number) {
    // Convert the number to hex.
    $hex_chars = "0123456789ABCDEF";
    $hex_value = '';
    while ($number != '0') {
      $hex_value = $hex_chars[bcmod($number,'16')] . $hex_value;
      $number = bcdiv($number,'16',0);
    }

    // Convert the hex number to an IPv6 address.
    $ip = trim(chunk_split(str_pad($hex_value, 32, '0', STR_PAD_LEFT), 4, ':'), ':');

    // Convert the IPv6 address to the two 32 bit numbers.
    return $this->ipToLong($ip);
  }

}
