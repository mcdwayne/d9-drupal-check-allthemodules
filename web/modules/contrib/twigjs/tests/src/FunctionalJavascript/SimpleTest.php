<?php

namespace Drupal\Tests\twigjs\FunctionalJavascript;

/**
 * Simple test about twig directly.
 *
 * @group twigjs
 */
class SimpleTest extends TwigjsTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['twigjs_test'];

  /**
   * Test the first simple thing.
   */
  public function testSimple() {
    $this->drupalGet('/twigjs_test/test_simple');
    $this->assertSelectorsAreIndentical('#twigjs_test_php', '#twigjs_test_js');
  }

}
