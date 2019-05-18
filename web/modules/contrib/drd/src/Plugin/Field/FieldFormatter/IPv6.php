<?php

namespace Drupal\drd\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'ipv6field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "ipv6field_formatter",
 *   label = @Translation("IP v6"),
 *   field_types = {
 *     "integer"
 *   }
 * )
 */
class IPv6 extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = ['#markup' => $this->long2ip6($item->value)];
    }

    return $elements;
  }

  /**
   * Convert long to IPv6 address.
   *
   * Requires PHP GMP library.
   *
   * @param string $ipv6long
   *   The long value.
   *
   * @return bool|string
   *   The ipv6 address if conversino succeeded, FALSE otherwise.
   *
   * @Credit f.wiessner@smart-weblications.net
   * http://www.php.net/manual/en/function.ip2long.php#94477
   */
  private function long2ip6($ipv6long) {
    if (!function_exists('gmp_strval')) {
      return '';
    }
    $ipv6 = '';
    $bin = gmp_strval(gmp_init($ipv6long, 10), 2);
    if (strlen($bin) < 128) {
      $pad = 128 - strlen($bin);
      for ($i = 1; $i <= $pad; $i++) {
        $bin = "0" . $bin;
      }
    }
    $bits = 0;
    while ($bits <= 7) {
      $bin_part = substr($bin, ($bits * 16), 16);
      $ipv6 .= dechex(bindec($bin_part)) . ":";
      $bits++;
    }
    // Compress.
    return inet_ntop(inet_pton(substr($ipv6, 0, -1)));
  }

}
