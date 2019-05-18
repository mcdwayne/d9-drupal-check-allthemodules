<?php

namespace Drupal\obfuscate;

/**
 * Class ObfuscateMailHtmlEntity.
 *
 * Obfuscates email addresses by relying on PHP only.
 *
 * @package Drupal\obfuscate
 */
class ObfuscateMailHtmlEntity implements ObfuscateMailInterface {

  /**
   * {@inheritdoc}
   */
  public function getObfuscatedLink($email, array $params = []) {
    if (!is_array($params)) {
      $params = [];
    }

    // Tell search engines to ignore obfuscated uri.
    if (!isset($params['rel'])) {
      $params['rel'] = 'nofollow';
    }

    $neverEncode = [
      '.',
      '@',
      '+',
      // Don't encode those as not fully supported by IE & Chrome.
    ];

    $urlEncodedEmail = '';
    for ($i = 0; $i < strlen($email); $i++) {
      // Encode 25% of characters.
      if (!in_array($email[$i], $neverEncode) && mt_rand(1, 100) < 25) {
        $charCode = ord($email[$i]);
        $urlEncodedEmail .= '%';
        $urlEncodedEmail .= dechex(($charCode >> 4) & 0xF);
        $urlEncodedEmail .= dechex($charCode & 0xF);
      }
      else {
        $urlEncodedEmail .= $email[$i];
      }
    }

    $obfuscatedEmail = $this->obfuscateEmail($email);
    $obfuscatedEmailUrl = $this->obfuscateEmail('mailto:' . $urlEncodedEmail);

    // @todo use twig template to allow override
    $link = '<a href="' . $obfuscatedEmailUrl . '"';
    foreach ($params as $param => $value) {
      $link .= ' ' . $param . '="' . htmlspecialchars($value) . '"';
    }
    $link .= '>' . $obfuscatedEmail . '</a>';
    $build = [
      '#theme' => 'email_link',
      '#link' => $link,
    ];
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function obfuscateEmail($email) {
    $alwaysEncode = ['.', ':', '@'];

    $result = '';

    // Encode string using oct and hex character codes.
    for ($i = 0; $i < strlen($email); $i++) {
      // Encode 25% of characters including several
      // that always should be encoded.
      if (in_array($email[$i], $alwaysEncode) || mt_rand(1, 100) < 25) {
        if (mt_rand(0, 1)) {
          $result .= '&#' . ord($email[$i]) . ';';
        }
        else {
          $result .= '&#x' . dechex(ord($email[$i])) . ';';
        }
      }
      else {
        $result .= $email[$i];
      }
    }

    return $result;
  }

}
