<?php

namespace Drupal\Tests\aws_cloud\Functional;

/**
 * Utitlity for testing.
 */
class Utils {

  /**
   * Get a random public ip address.
   *
   * @return string
   *   a random public ip address.
   */
  public static function getRandomPublicIp() {
    return implode('.', [rand(0, 254), rand(0, 255), rand(0, 255), rand(1, 255)]);
  }

  /**
   * Get a random private ip address.
   *
   * @return string
   *   a random private ip address.
   */
  public static function getRandomPrivateIp() {
    $private_ips = [
      implode('.', ['10', rand(0, 255), rand(0, 255), rand(1, 255)]),
      implode('.', ['172', rand(16, 31), rand(0, 255), rand(1, 255)]),
      implode('.', ['192', '168', rand(0, 255), rand(1, 255)]),
    ];
    return $private_ips[array_rand($private_ips)];
  }

  /**
   * Get a random cidr.
   *
   * @return string
   *   a random cidr.
   */
  public static function getRandomCidr() {
    $cidrs = [
      implode('.', ['10', rand(0, 255), rand(0, 255), rand(1, 255)]) . '/8',
      implode('.', ['172', rand(16, 31), '0', '0']) . '/16',
      implode('.', ['192', '168', rand(0, 255), '0']) . '/24',
    ];
    return $cidrs[array_rand($cidrs)];
  }

  /**
   * Get a random cidr v6.
   *
   * @return string
   *   a random cidr v6.
   */
  public static function getRandomCidrV6() {
    $cidrs = [
      implode(':', [
        sprintf('%04x', rand(1, 65535)),
        sprintf('%04x', rand(1, 65535)),
        sprintf('%04x', rand(1, 65535)),
        sprintf('%04x', rand(1, 65535)),
      ]) . '::/64',
      implode(':', [
        sprintf('%04x', rand(1, 65535)),
        sprintf('%04x', rand(1, 65535)),
        sprintf('%04x', rand(1, 65535)),
        sprintf('%04x', rand(1, 65535)),
        sprintf('%04x', rand(1, 65535)),
        sprintf('%04x', rand(1, 65535)),
      ]) . '::/96',
    ];
    return $cidrs[array_rand($cidrs)];
  }

  /**
   * Get a random from port.
   */
  public static function getRandomFromPort() {
    return rand(0, 37676);
  }

  /**
   * Get a random to port.
   */
  public static function getRandomToPort() {
    return rand(37677, 65535);
  }

  /**
   * Get a public DNS corresponding to specified region and ip address.
   *
   * @param string $region
   *   A region.
   * @param string $ip
   *   An ip address.
   *
   * @return string
   *   a public DNS.
   */
  public static function getPublicDns($region, $ip) {
    $ip_parts = explode('.', $ip);
    return sprintf('ec2-%d-%d-%d-%d.%s.compute.amazonaws.com',
      $ip_parts[0], $ip_parts[1], $ip_parts[2], $ip_parts[3], $region);
  }

  /**
   * Get a private DNS corresponding to specified region and ip address.
   *
   * @param string $region
   *   A region.
   * @param string $ip
   *   An ip address.
   *
   * @return string
   *   a private DNS.
   */
  public static function getPrivateDns($region, $ip) {
    $ip_parts = explode('.', $ip);
    return sprintf('ip-%d-%d-%d-%d.%s.compute.internal',
      $ip_parts[0], $ip_parts[1], $ip_parts[2], $ip_parts[3], $region);
  }

  /**
   * Get a random user id.
   *
   * @return int
   *   Random user id.
   */
  public static function getRandomUid() {
    return rand(1, 50);
  }

}
