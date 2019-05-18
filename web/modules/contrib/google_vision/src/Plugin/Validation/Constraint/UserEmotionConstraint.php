<?php

namespace Drupal\google_vision\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the node is assigned only a "leaf" term in the forum taxonomy.
 *
 * @Constraint(
 *   id = "UserEmotion",
 *   label = @Translation("User Emotion", context = "Validation"),
 * )
 */
class UserEmotionConstraint extends Constraint {

  public $message = 'Please upload a photo where you are smiling and happy';
}
