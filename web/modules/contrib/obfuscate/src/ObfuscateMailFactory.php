<?php

namespace Drupal\obfuscate;

/**
 * Static factory.
 */
class ObfuscateMailFactory {

  const HTML_ENTITY = 'html_entity';

  const ROT_13 = 'rot_13';

  /**
   * Static factory.
   *
   * @param string $method
   *   Email obfuscation method.
   *
   * @return \Drupal\obfuscate\ObfuscateMailInterface
   *   Email obfuscation method.
   */
  public static function get($method) {

    /** @var \Drupal\obfuscate\ObfuscateMailInterface $obfuscateMail */
    $obfuscateMail = NULL;

    switch ($method) {
      case self::HTML_ENTITY:
        $obfuscateMail = new ObfuscateMailHtmlEntity();
        break;

      case self::ROT_13:
        $obfuscateMail = new ObfuscateMailROT13();
        break;

      default:
        throw new \InvalidArgumentException(t('Unknown obfuscation method'));
    }

    return $obfuscateMail;
  }

}
