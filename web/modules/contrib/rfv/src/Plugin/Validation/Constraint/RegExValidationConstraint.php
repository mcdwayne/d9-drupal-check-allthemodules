<?php

namespace Drupal\regex_field_validation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the node.
 *
 * @Constraint(
 *   id = "RegExValidationConstraint",
 *   label = @Translation("RegEx Validation"),
 * )
 */
class RegExValidationConstraint extends Constraint {
  public $regex;
  public $errorMessage;

  /**
   * Class constructor.
   */
  public function __construct($options = NULL) {
    if (!empty($options) && is_array($options)) {
      $options = [
        'regex' => $options['regex'],
        'errorMessage' => $options['errorMessage'],
      ];
      parent::__construct($options);
    }
    else {
      drupal_set_message('There was an issue with the RegExValidation module initialisation', 'error');
    };
  }

}
