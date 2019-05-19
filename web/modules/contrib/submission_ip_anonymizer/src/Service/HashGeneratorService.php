<?php
/**
 * Created by PhpStorm.
 * User: adam.gieron
 * Date: 09/07/2018
 * Time: 10:03
 */

namespace Drupal\submission_ip_anonymizer\Service;

/**
 * Class HashGeneratorService
 * @package Drupal\submission_ip_anonymizer\Service
 */
class HashGeneratorService {


  /**
   * @param $string
   * @return string
   *
   *   BC Math library to translate a MD5 hash into a Base-90 string.
   *   This converts a 32 char string into a 20 char one
   *
   */
  public function generateHash($string) {
    $hash = md5($string);
    $chars16 = [
      '0' => 0,
      '1' => 1,
      '2' => 2,
      '3' => 3,
      '4' => 4,
      '5' => 5,
      '6' => 6,
      '7' => 7,
      '8' => 8,
      '9' => 9,
      'a' => 10,
      'b' => 11,
      'c' => 12,
      'd' => 13,
      'e' => 14,
      'f' => 15,
    ];
    $base10 = '0';
    for ($i = strlen($hash) - 1; $i > 0; $i--) {
      $base10 = bcadd($base10, bcmul($chars16[$hash[$i]], bcpow(16, $i)));
    }
    $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ,;.:-_+*?!$%&@#~^=/<>[](){}`';
    $base = (string) strlen($chars);
    $baseX = '';
    while (bccomp($base10, $base) === 1 || bccomp($base10, $base) === 0) {
      $baseX = substr($chars, bcmod($base10, $base), 1) . $baseX;
      $base10 = preg_replace('/\.\d*$/', '', bcdiv($base10, $base));
    }
    return substr($chars, $base10, 1) . $baseX;
  }
}