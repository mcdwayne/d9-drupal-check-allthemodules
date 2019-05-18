<?php

namespace Drupal\fancy_login\TestBase;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;

/**
 * Base class providing various functions used in functional tests.
 *
 * @group fancy_login
 */
class FancyLoginJavascriptTestBase extends JavascriptTestBase {

  /**
   * Asserts the HTTP status code is equal to the one given.
   *
   * @param int $statusCode
   *   The status code to compare.
   */
  public function assertStatusCodeEquals($statusCode) {
    $this->assertSession()->statusCodeEquals($statusCode);
  }

  /**
   * Assert that an element exists on the page.
   */
  public function assertElementExists($selector) {
    $this->assertSession()->elementExists('css', $selector);
  }

  /**
   * Assert that the element for the given xpath exists.
   */
  public function assertElementExistsXpath($selector) {
    $this->assertSession()->elementExists('xpath', $selector);
  }

  /**
   * Gets the resulting HTML.
   */
  public function getHtml() {
    $this->assertEquals('', $this->getSession()->getPage()->getHTML());
  }

  /**
   * Click the element at the given xpath.
   */
  public function clickByXpath($path) {
    $this->getSession()->getPage()->find('xpath', $path)->click();
  }

  /**
   * Inserts a text value into a text field.
   */
  public function fillTextValue($htmlID, $value) {
    if (preg_match('/^#/', $htmlID)) {
      $htmlID = substr($htmlID, 1);
    }

    $this->getSession()->getPage()->fillField($htmlID, $value);
  }

}
