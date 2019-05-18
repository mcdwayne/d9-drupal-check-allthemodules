<?php

namespace CleverReach\BusinessLogic\Entity;

/**
 * This class is intended only for migrating tasks from old prefixed format to new format with origin
 * and should not be used elsewhere.
 */
class TagInOldFormat extends Tag {

  /**
   * This class is intended only for migrating tasks from old prefixed format to new format with origin
   * and should not be used elsewhere.
   *
   * @param string $name
   *
   * @throws \InvalidArgumentException Name cannot be empty
   */
  public function __construct($name) {
    parent::__construct($name, '');
  }

  /**
   * @inheritdoc
   */
  protected function validate() {
    if (empty($this->name)) {
      throw new \InvalidArgumentException('Name and Type parameters cannot be empty!');
    }
  }

}
