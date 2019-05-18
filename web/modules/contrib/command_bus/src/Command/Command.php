<?php

namespace Drupal\command_bus\Command;

use Drupal\command_bus\Validator\ValidatorInterface;
use Drupal\command_bus\Validator\ValidCommandValidator;

/**
 * Class Command.
 *
 * @package Drupal\command_bus\Command
 */
abstract class Command implements CommandInterface {

  /**
   * The pre validators.
   *
   * @var array|\Drupal\command_bus\Validator\ValidatorInterface[]
   */
  private $preValidators = [];

  /**
   * The post validators.
   *
   * @var array|\Drupal\command_bus\Validator\ValidatorInterface[]
   */
  private $postValidators = [];

  /**
   * Returns the pre validators.
   *
   * @return array|\Drupal\command_bus\Validator\ValidatorInterface[]
   *   The pre validators.
   */
  final public function getPreValidators() {
    $validation = [new ValidCommandValidator()];

    return array_merge($this->preValidators, $validation);
  }

  /**
   * Returns the post validators.
   *
   * @return array|\Drupal\command_bus\Validator\ValidatorInterface[]
   *   The post validators.
   */
  final public function getPostValidators() {
    return $this->postValidators;
  }

  /**
   * Adds a pre validator.
   *
   * @param \Drupal\command_bus\Validator\ValidatorInterface $validator
   *   The pre validator.
   */
  final protected function addPreValidator(ValidatorInterface $validator) {
    $this->preValidators[] = $validator;
  }

  /**
   * Adds a post validator.
   *
   * @param \Drupal\command_bus\Validator\ValidatorInterface $validator
   *   The post validator.
   */
  final protected function addPostValidator(ValidatorInterface $validator) {
    $this->postValidators[] = $validator;
  }

}
