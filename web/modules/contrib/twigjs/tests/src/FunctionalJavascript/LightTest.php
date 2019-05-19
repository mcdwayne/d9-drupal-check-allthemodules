<?php

namespace Drupal\Tests\twigjs\FunctionalJavascript;

/**
 * Test that light templates works.
 *
 * @group twigjs
 */
class LightTest extends TwigjsTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['twigjs_test'];

  /**
   * Test the output is the same.
   */
  public function testLight() {
    $this->drupalGet('/twigjs_test/test_light');
    $this->assertSelectorsAreIndentical('#twigjs-test-light-wrapper', '#twigjs-test-light-wrapper-js');
  }

}
