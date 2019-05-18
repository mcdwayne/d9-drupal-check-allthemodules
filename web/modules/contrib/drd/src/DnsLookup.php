<?php

namespace Drupal\drd;

/**
 * Class DnsLookup.
 *
 * @package Drupal\drd
 */
class DnsLookup {

  /**
   * Lookup ip v4 and v6 addresses for a host name.
   *
   * @param string $name
   *   The hostname for which to determine ip addresses.
   * @param array $ipv4
   *   List of ip v4 addresses.
   * @param array $ipv6
   *   List of ip v6 addresses.
   */
  public function lookup($name, array &$ipv4, array &$ipv6) {
    static $results = [];

    if (isset($results[$name])) {
      $ipv4 = $results[$name]['ipv4'];
      $ipv6 = $results[$name]['ipv6'];
      return;
    }

    $records = @dns_get_record($name, DNS_ALL);
    if (!empty($records)) {
      foreach ($records as $record) {
        switch ($record['type']) {
          case 'A':
            $ipv4[] = ip2long($record['ip']);
            break;

          case 'A6':
            if (function_exists('gmp_strval')) {
              $ipv6[] = self::ip2long6($record['ip']);
            }
            break;
        }
      }
    }

    $results[$name] = [
      'ipv4' => $ipv4,
      'ipv6' => $ipv6,
    ];
  }

  /**
   * Convert IPv6 address to long.
   *
   * Requires PHP GMP library.
   *
   * @param string $ipv6
   *   The ip v6 address.
   *
   * @return int|string
   *   The long value representing the ip address.
   *
   * @Credit f.wiessner@smart-weblications.net
   * http://www.php.net/manual/en/function.ip2long.php#94477
   */
  public function ip2long6($ipv6) {
    if (!function_exists('gmp_strval')) {
      return 0;
    }
    $ipv6long = '';
    $ip_n = inet_pton($ipv6);
    // 16 x 8 bit = 128bit.
    $bits = 15;
    while ($bits >= 0) {
      $bin = sprintf("%08b", (ord($ip_n[$bits])));
      $ipv6long = $bin . $ipv6long;
      $bits--;
    }
    return gmp_strval(gmp_init($ipv6long, 2), 10);
  }

}
