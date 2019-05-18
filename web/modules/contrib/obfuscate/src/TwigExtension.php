<?php

namespace Drupal\obfuscate;

/**
 * Twig extension that wraps obfuscation methods.
 */
class TwigExtension extends \Twig_Extension {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'obfuscateMail';
  }

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    $filters = [
      new \Twig_SimpleFilter(
        'obfuscateMail',
        [$this, 'obfuscateMail']
      ),
    ];
    return $filters;
  }

  /**
   * Replaces email string by an obfuscated email link.
   *
   * @param string $mail
   *   A plain email address.
   *
   * @return array
   *   The entered obfuscated email as a link.
   */
  public function obfuscateMail($mail) {
    /** @var \Drupal\obfuscate\ObfuscateMailInterface $obfuscateMail */
    $obfuscateMail = \Drupal::service('obfuscate_mail');
    $build = $obfuscateMail->getObfuscatedLink($mail);
    return $build;
  }

}
