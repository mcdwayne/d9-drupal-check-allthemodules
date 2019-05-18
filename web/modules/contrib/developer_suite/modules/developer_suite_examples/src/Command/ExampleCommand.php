<?php

namespace Drupal\developer_suite_examples\Command;

use Drupal\developer_suite\Command\Command;
use Drupal\developer_suite_examples\Validate\ExampleCommandPostValidate;
use Drupal\developer_suite_examples\Validate\ExampleCommandPreValidate;

/**
 * Class ExampleCommand.
 *
 * @package Drupal\developer_suite_examples\Command
 */
class ExampleCommand extends Command {

  /**
   * Some example data which can be used in your command handler class.
   *
   * @var array
   */
  public $data;

  /**
   * ExampleCommand constructor.
   *
   * (optional)You could pass some data through the constructor needed in your
   * command handler. Furthermore you can create some helper methods which in
   * turn could be used in your custom command validators.
   *
   * @param array $data
   *   Some example data which can be used in your command handler class.
   *
   * @see \Drupal\developer_suite_examples\Form\ExampleForm
   */
  public function __construct(array $data) {
    // (optional) Set the command data.
    $this->data = $data;

    // (optional) Add some custom pre validators which are run pre command
    // execution. If any of the pre validators fail the command is not run and
    // the preValidationFailed() method in your command handler class gets
    // invoked passing a \Drupal\developer_suite\Collection\ViolationCollection
    // object containing the violations.
    $this->addPreValidator(new ExampleCommandPreValidate('Pre validation failed.'));

    // (optional) Add some custom post validators which are run post command
    // execution. If any of the pre validators fail the command is not run and
    // the preValidationFailed() method in your command handler class gets
    // invoked passing a \Drupal\developer_suite\Collection\ViolationCollection
    // object containing the violations.
    $this->addPostValidator(new ExampleCommandPostValidate('Post validation failed.'));
  }

  /**
   * Returns something.
   */
  public function getSomething() {

  }

  /**
   * Returns the command handler plugin ID.
   *
   * The ID that gets returned by this method should be exactly the same as
   * your CommandHandler plugin ID.
   *
   * @see \Drupal\developer_suite_examples\Plugin\CommandHandler\ExampleCommandHandler
   *
   * @return string
   *   The command handler plugin ID.
   */
  public function getCommandHandlerPluginId() {
    return 'example_command_handler';
  }

}
