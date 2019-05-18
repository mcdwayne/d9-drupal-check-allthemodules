<?php

namespace Drupal\obfuscate;

/**
 * Class ObfuscateMailROT13.
 *
 * Based on the Propaganistas vendor.
 *
 * @see https://packagist.org/packages/propaganistas/email-obfuscator
 * @see https://github.com/Propaganistas/Email-Obfuscator
 *
 * @package Drupal\obfuscate
 */
class ObfuscateMailROT13 implements ObfuscateMailInterface {

  /**
   * ROT 13 class name of 'obfuscate-r13'.
   *
   * This is not a dependency of ROT 13, it is merely a way
   * to remove hints for spammers.
   */
  const OBFUSCATE_ROT_13_CSS_CLASS = 'boshfpngr-e13';

  /**
   * {@inheritdoc}
   */
  public function getObfuscatedLink($email, array $params = []) {
    $build = [
      '#theme' => 'email_rot13_link',
      '#link' => $this->obfuscateEmail($email),
    ];
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function obfuscateEmail($string) {
    // Propaganistas vendor provides this method as a global function.
    // So it is copied here instead of using Composer.
    // The inline <script> and <noscript> and inline styles
    // for css fallback have been replaced as well.
    // Casting $string to a string allows passing of objects
    // implementing the __toString() magic method.
    $string = (string) $string;

    // Safeguard string.
    $safeguard = '$%$!!$%$';

    // Define patterns for extracting emails.
    // The vendor selection pattern has been simplified because
    // most of the work has already been done and at this stage the string
    // that is being passed is already an email address.
    $patterns = [
      // Plain emails.
      '|[_a-z0-9-]+(?:\.[_a-z0-9-]+)*@[a-z0-9-]+(?:\.[a-z0-9-]+)*(?:\.[a-z]{2,3})|i',
    ];

    foreach ($patterns as $pattern) {
      $string = preg_replace_callback($pattern, function ($parts) use ($safeguard) {

        // Clean up element parts.
        $parts = array_map('trim', $parts);

        // ROT13 implementation for JS-enabled browsers.
        $js = '<span class="js-enabled">' . str_rot13($parts[0]) . '</span>';

        // Reversed direction implementation for non-JS browsers.
        if (stripos($parts[0], '<a') === 0) {
          // Mailto tag; if link content equals the email,
          // just display the email, otherwise display a formatted string.
          $nojs = ($parts[1] == $parts[3]) ? $parts[1] : (' > ' . $parts[1] . ' < ' . $parts[3]);
        }
        else {
          // Plain email; display the plain email.
          $nojs = $parts[0];
        }
        $nojs = '<span class="js-disabled">' . strrev($nojs) . '</span>';

        // Safeguard the obfuscation so it won't get picked up
        // by the next iteration.
        return str_replace('@', $safeguard, $js . $nojs);
      }, $string);
    }

    // Revert all safeguards.
    return str_replace($safeguard, '@', $string);
  }

}
