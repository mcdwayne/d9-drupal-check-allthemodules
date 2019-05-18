<?php

namespace Drupal\Tests\dynamic_date\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;

/**
 * Test basic functionality.
 *
 * @group dynamic_date
 */
class DynamicDateIntegrationTest extends JavascriptTestBase {
  /**
   * {@inheritdoc}
   */
  public static $modules = ['dynamic_date_test'];

  /**
   * Tests if module works as expected.
   */
  public function testElementWorks() {
    $this->drupalGet('<front>');
    $session_page = $this->getSession()->getPage();
    /** @var \Behat\Mink\Element\NodeElement $els */
    $els = $session_page->find('css', '.past [data-is-timeago]');
    $this->assertNotEmpty($els);
    $this->assertEquals($els->getAttribute('data-js-date'), 1);
    $this->assertSession()->elementTextContains('css', '[data-is-timeago]', 'a few seconds ago');
  }

  /**
   * Test if the "ensure past" thing works.
   */
  public function testEnsurePastWorks() {
    $this->drupalGet('<front>');
    $session_page = $this->getSession()->getPage();
    /** @var \Behat\Mink\Element\NodeElement $els */
    $els = $session_page->find('css', '.future [data-is-timeago]');
    $this->assertNotEmpty($els);
    $this->assertEquals($els->getAttribute('data-js-date'), 1);
    $this->assertSession()->elementTextContains('css', '[data-is-timeago]', 'a few seconds ago');
  }

}
