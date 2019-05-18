<?php

namespace Drupal\rules_scheduler_test;

use Drupal\rules_scheduler\DefaultTaskHandler;

/**
 * Test task handler class.
 */
class TestTaskHandler extends DefaultTaskHandler {

  /**
   * {@inheritdoc}
   */
  public function runTask() {
    $data = $this->getTask()->getData();

    // Set the variable defined in the test to TRUE.
    $testSettings = \Drupal::configFactory()->getEditable('rules_scheduler_test.settings');
    $testSettings->set($data['variable'], TRUE)->save();
  }

}
