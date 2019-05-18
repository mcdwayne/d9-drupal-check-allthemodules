<?php

namespace Drupal\developer_suite\Command;

use Drupal\developer_suite\Validator\BaseValidatorInterface;

/**
 * Class Command.
 *
 * @package Drupal\developer_suite\Command
 */
abstract class Command implements CommandInterface {

  /**
   * The pre validators.
   *
   * @var array|\Drupal\developer_suite\Validator\BaseValidatorInterface[]
   */
  private $preValidators = [];

  /**
   * The post validators.
   *
   * @var array|\Drupal\developer_suite\Validator\BaseValidatorInterface[]
   */
  private $postValidators = [];

  /**
   * Returns the pre validators.
   *
   * @return array|\Drupal\developer_suite\Validator\BaseValidatorInterface[]
   *   The pre validators.
   */
  final public function getPreValidators() {
    return $this->preValidators;
  }

  /**
   * Returns the post validators.
   *
   * @return array|\Drupal\developer_suite\Validator\BaseValidatorInterface[]
   *   The post validators.
   */
  final public function getPostValidators() {
    return $this->postValidators;
  }

  /**
   * Returns the command handler plugin ID.
   *
   * @return string
   *   The command handler plugin ID.
   */
  abstract public function getCommandHandlerPluginId();

  /**
   * Adds a pre validator.
   *
   * @param \Drupal\developer_suite\Validator\BaseValidatorInterface $validator
   *   The pre validator.
   */
  final protected function addPreValidator(BaseValidatorInterface $validator) {
    $this->preValidators[] = $validator;
  }

  /**
   * Adds a post validator.
   *
   * @param \Drupal\developer_suite\Validator\BaseValidatorInterface $validator
   *   The post validator.
   */
  final protected function addPostValidator(BaseValidatorInterface $validator) {
    $this->postValidators[] = $validator;
  }

}
