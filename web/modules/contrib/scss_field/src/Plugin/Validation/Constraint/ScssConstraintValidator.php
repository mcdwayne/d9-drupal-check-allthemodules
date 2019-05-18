<?php

namespace Drupal\scss_field\Plugin\Validation\Constraint;

use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraint;
use Leafo\ScssPhp\Compiler;
use Leafo\ScssPhp\Exception\ParserException;

/**
 * Validates the Scss constraint.
 */
class ScssConstraintValidator extends ConstraintValidator {

  /**
   * The SCSS compiler.
   *
   * @var \Leafo\ScssPhp\Compiler
   */
  protected $compiler;

  /**
   * Cached compiled CSS.
   *
   * @var string|null
   */
  protected $compiled;

  /**
   * {@inheritdoc}
   */
  public function initialize(ExecutionContextInterface $context) {
    parent::initialize($context);
    $this->compiled = NULL;
    if (!$this->compiler) {
      $this->compiler = new Compiler();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validate($item, Constraint $constraint) {

    // If there is no value we don't need to validate anything.
    if (!isset($item)) {
      return NULL;
    }

    try {
      $value = $item->get('value')->getValue();
      $this->compiled = $this->compiler->compile($value);
    }
    catch (ParserException $e) {
      $this->context->addViolation($constraint->invalidScss, [
        '%error' => $e->getMessage(),
      ]);
    }
  }

}
