<?php

namespace CleverReach\Tests\GenericTests\TestComponents;

use CleverReach\Infrastructure\TaskExecution\Task;

/**
 *
 */
class FakeTask extends Task {
  /**
   * @var string*/
  private $name;

  /**
   * FakeTask constructor.
   *
   * @param string $name
   */
  public function __construct($name) {
    $this->name = $name;
  }

  /**
   *
   */
  public function serialize() {
    return serialize([
      'name' => $this->name,
    ]);
  }

  /**
   *
   */
  public function unserialize($serialized) {
    $data = unserialize($serialized);
    $this->name = $data['name'];
  }

  /**
   *
   */
  public function execute() {
    // This method was intentionally left blank because this task is for testing purposes.
  }

}
