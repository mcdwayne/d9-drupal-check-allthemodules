<?php

namespace Drupal\amp\AMP;

use Lullabot\AMP\Validate\ParsedValidatorRules;
use Lullabot\AMP\Spec\ValidationRulesFactory;

/**
 * Drupal override of Lullabot\ParsedValidatorRules.
 *
 * Adds alter hook to rules array.
 */
class DrupalParsedValidatorRules extends ParsedValidatorRules {

  /**
   * {@inheritdoc}
   */
  public static function getSingletonParsedValidatorRules(){
    if (!empty(self::$parsed_validator_rules_singleton)) {
      return self::$parsed_validator_rules_singleton;
    }
    else {
      $rules = self::updatedRules();
      self::$parsed_validator_rules_singleton = new self($rules);
      return self::$parsed_validator_rules_singleton;
    }
  }

  /**
   * Adds an alter hook to update AMP rules that might be causing problems.
   *
   * The original code in Lullabot/AMP is out of date with the current AMP
   * specifications. Updating that code is a non-trivial job that may not get
   * done any time soon. This is a work-around to allow changes to the rules.
   *
   * @TODO Add caching.
   *
   * @see https://github.com/Lullabot/amp-library/blob/master/src/Spec/validator-generated.php
   */
  public static function updatedRules() {

    $rules = ValidationRulesFactory::createValidationRules();

    // Allow other modules to alter the rules.
    \Drupal::moduleHandler()->alter('amp_rules', $rules);

    return $rules;
  }

}
