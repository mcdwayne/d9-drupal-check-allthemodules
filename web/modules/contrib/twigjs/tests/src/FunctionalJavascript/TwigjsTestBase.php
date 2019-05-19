<?php

namespace Drupal\Tests\twigjs\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;

/**
 * Base class for twigjs test cases.
 */
abstract class TwigjsTestBase extends JavascriptTestBase {

  /**
   * Test that a php and js field exists and has the same text.
   */
  protected function assertSelectorsAreIndentical($selector1, $selector2) {
    $page = $this->getSession()->getPage();
    $php_field = $page->find('css', $selector1);
    $this->assertNotEmpty($php_field);
    $js_field = $page->find('css', $selector2);
    $this->assertNotEmpty($js_field);
    $this->assertEquals($js_field->getText(), $php_field->getText());
  }

}
