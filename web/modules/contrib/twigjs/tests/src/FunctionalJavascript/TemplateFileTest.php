<?php

namespace Drupal\Tests\twigjs\FunctionalJavascript;

/**
 * Test that the php and js output is similar for a core template.
 *
 * @group twigjs
 */
class TemplateFileTest extends TwigjsTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['twigjs_test'];

  /**
   * Test that the output is the same.
   */
  public function testTemplateFile() {
    $this->drupalGet('/twigjs_test/test_file');
    $this->assertSelectorsAreIndentical('#twigjs-test-file-php', '#twigjs-test-file-js');
  }

}
