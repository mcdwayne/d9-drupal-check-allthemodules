<?php

namespace Drupal\form_alter_service\Annotation;

use Drupal\Core\Form\FormStateInterface;
use Drupal\form_alter_service\FormAlterBase;
use Reflection\Validator\MethodValidator;
use Reflection\Validator\ArgumentSpecification;
use Reflection\Validator\Annotation\ReflectionValidatorMethodAnnotationInterface;

/**
 * Base annotation for defining a handler.
 */
abstract class FormHandler implements ReflectionValidatorMethodAnnotationInterface {

  /**
   * Priority of a handler.
   *
   * @var int
   *
   * @Assert\Type("int")
   */
  public $priority = 0;

  /**
   * Strategy of a handler.
   *
   * @var string
   *
   * @Enum({"append", "prepend"})
   */
  public $strategy = 'append';

  /**
   * Returns the type of handler ("#submit" or "#validate").
   *
   * @return string
   *   The type of handler.
   */
  abstract public function __toString(): string;

  /**
   * {@inheritdoc}
   */
  public function validate(\ReflectionMethod $method) {
    (new MethodValidator($method, FormAlterBase::class))
      ->addArgument(
        (new ArgumentSpecification('form'))
          ->setType('array')
          ->setOptional(FALSE)
          // The "$form" argument for validation handlers must be passed by
          // reference. For submit - no way.
          ->setPassedByReference(is_a(static::class, FormValidate::class, TRUE))
      )
      ->addArgument(
        (new ArgumentSpecification('form_state'))
          ->setType(FormStateInterface::class)
          ->setOptional(FALSE)
          ->setPassedByReference(FALSE)
      );
  }

}
