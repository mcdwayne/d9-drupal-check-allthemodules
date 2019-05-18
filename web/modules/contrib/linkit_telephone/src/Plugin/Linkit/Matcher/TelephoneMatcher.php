<?php

namespace Drupal\linkit_telephone\Plugin\Linkit\Matcher;

use Drupal\Component\Utility\Html;
use Drupal\linkit\MatcherBase;
use Drupal\linkit\Suggestion\DescriptionSuggestion;
use Drupal\linkit\Suggestion\SuggestionCollection;
use Brick\PhoneNumber\PhoneNumber;
use Brick\PhoneNumber\PhoneNumberParseException;

/**
 * Provides linkit matcher for telephone numbers.
 *
 * @Matcher(
 *   id = "telephone",
 *   label = @Translation("Telephone"),
 * )
 */
class TelephoneMatcher extends MatcherBase {

  /**
   * {@inheritdoc}
   */
  public function execute($string) {
    $suggestions = new SuggestionCollection();

    $config = \Drupal::config('linkit_telephone.settings');
    $default_region_code = $config->get('default_region_code');

    $parsed = null;
    try {
      $parsed = PhoneNumber::parse($string, $default_region_code);
    }
    catch (PhoneNumberParseException $e) {
      return $suggestions;
    }

    // Check for an phone number then return a telephone match and create a
    // tel: link.
    if ($parsed && $parsed->isValidNumber()) {
      $suggestion = new DescriptionSuggestion();
      $suggestion->setLabel($this->t('Telephone @tel', ['@tel' => $string]))
        ->setPath('tel:' . Html::escape($string))
        ->setGroup($this->t('Telephone'))
        ->setDescription($this->t('Opens a telephone dialer to @tel', ['@tel' => $string]));

      $suggestions->addSuggestion($suggestion);
    }
    return $suggestions;
  }

}
