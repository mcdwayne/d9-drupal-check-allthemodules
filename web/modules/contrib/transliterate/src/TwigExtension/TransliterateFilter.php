<?php

namespace Drupal\transliterate\TwigExtension;


class TransliterateFilter extends \Twig_Extension {

  /**
   * Generates a list of all Twig filters that this extension defines.
   */
  public function getFilters() {
    return [
      new \Twig_SimpleFilter('transliterate', array($this, 'transliterateString')),
    ];
  }

  /**
   * Gets a unique identifier for this Twig extension.
   */
  public function getName() {
    return 'transliterate.twig_extension';
  }

  /**
   * Transliterates a string
   */
  public static function transliterateString($string) {
    return transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $string);
  }

}
