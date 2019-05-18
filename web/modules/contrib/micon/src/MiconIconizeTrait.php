<?php

namespace Drupal\micon;

use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Wrapper methods for \Drupal\micon\MiconIconize.
 */
trait MiconIconizeTrait {

  /**
   * Transforms a string into an icon + string.
   *
   * This can be used interchangeably with the
   * \Drupal\Core\StringTranslation\StringTranslationTrait.
   *
   * @param string $string
   *   A string containing the English text to translate.
   * @param array $args
   *   (optional) An associative array of replacements to make after
   *   translation. Based on the first character of the key, the value is
   *   escaped and/or themed. See
   *   \Drupal\Component\Render\FormattableMarkup::placeholderFormat() for
   *   details.
   * @param array $options
   *   (optional) An associative array of additional options, with the following
   *   elements:
   *   - 'langcode' (defaults to the current language): A language code, to
   *     translate to a language other than what is used to display the page.
   *   - 'context' (defaults to the empty context): The context the source
   *     string belongs to.
   *
   * @return \Drupal\Core\Render\Markup
   *   An object that, when cast to a string, returns the icon markup and
   *   translated string.
   *
   * @see \Drupal\Core\StringTranslation\StringTranslationTrait::t()
   *
   * @ingroup sanitization
   */
  protected function micon($string, array $args = array(), array $options = array()) {
    return new MiconIconize($string, $args, $options, $this->getStringTranslation());
  }

  /**
   * Gets the string translation service.
   *
   * @return \Drupal\Core\StringTranslation\TranslationInterface
   *   The string translation service.
   */
  protected function getMiconStringTranslation() {
    if (!$this->stringTranslation) {
      $this->stringTranslation = \Drupal::service('string_translation');
    }
    return $this->stringTranslation;
  }

  /**
   * Sets the string translation service to use.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The string translation service.
   *
   * @return $this
   */
  public function setMiconStringTranslation(TranslationInterface $translation) {
    $this->stringTranslation = $translation;
    return $this;
  }

}
