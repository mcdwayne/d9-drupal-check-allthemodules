<?php

namespace Drupal\drupal_yext\Tests;

use Drupal\drupal_yext\Form\YextSettingsForm;
use PHPUnit\Framework\TestCase;

/**
 * Test YextSettingsForm.
 *
 * @group myproject
 */
class YextSettingsFormTest extends TestCase {

  /**
   * Smoke test.
   */
  public function testSmoke() {
    $object = $this->getMockBuilder(YextSettingsForm::class)
      // NULL = no methods are mocked; otherwise list the methods here.
      ->setMethods(NULL)
      ->disableOriginalConstructor()
      ->getMock();

    $this->assertTrue(is_object($object));
  }

}
