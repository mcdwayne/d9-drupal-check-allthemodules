<?php

declare(strict_types = 1);

namespace Drupal\sendwithus;

/**
 * Provides email parsing functionality.
 */
trait EmailParserTrait {

  /**
   * Parses the addresses.
   *
   * @param string $addresses
   *   The RFC 2822 list of email addresses.
   *
   * @return array
   *   The array of addresses.
   */
  public function parseAddresses(string $addresses) {
    $addresses = explode(',', $addresses);

    return array_map(function (string $element) {
      if (preg_match('/^\s*(.+?)\s*<\s*([^>]+)\s*>$/', trim($element), $matches)) {
        list(, $name, $email) = $matches;

        return ['address' => $email, 'name' => $name];
      }
      return ['address' => trim($element)];
    }, $addresses);


  }

}
