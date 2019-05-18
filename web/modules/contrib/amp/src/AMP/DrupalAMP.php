<?php

namespace Drupal\amp\AMP;

use Lullabot\AMP\AMP;
use Lullabot\AMP\Spec\ValidatorRules;
use Lullabot\AMP\Spec\TagSpec;
use Lullabot\AMP\Spec\AttrSpec;
use Lullabot\AMP\Spec\ValidationRulesFactory;
use Drupal\amp\AMP\DrupalParsedValidatorRules;

/**
 * {@inheritdoc}
 */
class DrupalAMP extends AMP {

  /**
   * Override of Lullabot\AMP\AMP
   *
   * The DrupalParsedValidator override adds an alter hook to the rules array,
   * giving us more control over what is removed or not.
   */
  public function __construct() {
    $this->parsed_rules = DrupalParsedValidatorRules::getSingletonParsedValidatorRules();
    $this->rules = $this->parsed_rules->rules;
  }

}
