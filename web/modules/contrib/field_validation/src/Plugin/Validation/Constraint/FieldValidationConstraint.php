<?php

namespace Drupal\field_validation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the node.
 *
 * @Constraint(
 *   id = "FieldValidationConstraint",
 *   label = @Translation("Field Validation Constraint"),
 * )
 */
class FieldValidationConstraint extends Constraint {
    public $ruleset_name;
    public $rule_uuid;
    public function __construct($options = null){
        if (null !== $options && !is_array($options)) {
            $options = array(
                'ruleset_name' => $options,
                'rule_uuid' => $options,
            );
        }

        parent::__construct($options);

        if (null === $this->ruleset_name || null === $this->rule_uuid) {
            throw new MissingOptionsException(sprintf('Either option "ruleset_name" or "rule_uuid" must be given for constraint %s', __CLASS__), array('ruleset_name', 'rule_uuid'));
        }
    }
}
