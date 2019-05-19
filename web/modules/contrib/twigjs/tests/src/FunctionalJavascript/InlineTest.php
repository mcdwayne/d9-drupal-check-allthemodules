<?php

namespace Drupal\Tests\twigjs\FunctionalJavascript;

/**
 * Test that inline templates works.
 *
 * @group twigjs
 */
class InlineTest extends TwigjsTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['twigjs_test'];

  /**
   * Test the output is the same.
   */
  public function testTemplateFile() {
    $this->drupalGet('/twigjs_test/test_inline');
    $this->assertSelectorsAreIndentical('#twigjs-test-controller-wrapper', '#twigjs-test-controller-wrapper-js');
  }
}
