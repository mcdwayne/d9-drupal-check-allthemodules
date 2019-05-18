<?php

namespace Drupal\push_notifications\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraints\Choice;

/**
 * Validate that the language code passed is valid..
 *
 * @Constraint(
 *   id = "PushNotificationsTokenLanguage",
 *   label = @Translation("Language code in token entity", context = "Validation")
 * )
 */
class PushNotificationsTokenLanguageConstraint extends Choice {

  public $message = 'The language code %value is not a valid language code.';

}
