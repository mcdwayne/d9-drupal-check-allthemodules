<?php

namespace Drupal\Tests\snippet_manager\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\FunctionalJavascriptTests\DrupalSelenium2Driver;

/**
 * Tests the functionality of the snippet filter.
 *
 * @group snippet_manager
 */
class OverviewFilterTest extends JavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  protected $minkDefaultDriverClass = DrupalSelenium2Driver::class;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['snippet_manager_test'];

  /**
   * Test callback.
   */
  public function testSnippetFilter() {

    $admin_user = $this->drupalCreateUser(['administer snippets']);
    $this->drupalLogin($admin_user);

    $this->drupalGet('admin/structure/snippet');

    $total_snippets = 3;

    // Expect 3 snippets available.
    self::assertEquals($total_snippets, $this->countRows(), 'Snippets are not filtered yet.');
    $this->assertEmptyRow(FALSE);

    $this->setFilterValue('search', 'beta');
    self::assertEquals(1, $this->countRows(), 'Snippets are filtered.');
    $this->assertEmptyRow(FALSE);

    // Let's do search by snippet id only.
    $this->setFilterValue('search', 'alpha');
    self::assertEquals(1, $this->countRows(), 'Alpha snippet was found.');

    $this->setFilterValue('search', 'non existing snippet');
    self::assertEquals(0, $this->countRows(), 'All snippets are hidden.');
    $this->assertEmptyRow(TRUE);

    // Reset the search string and check that we are back to the initial stage.
    $this->setFilterValue('search', '');
    self::assertEquals($total_snippets, $this->countRows(), 'Snippets are not filtered.');
    $this->assertEmptyRow(FALSE);

    // Filter by usage and status.
    $this->setFilterValue('usage', 'page');
    $this->setFilterValue('status', 'enabled');
    self::assertEquals(2, $this->countRows(), 'Snippets are filtered.');

    $this->setFilterValue('usage', 'block');
    self::assertEquals(1, $this->countRows(), 'Snippets are filtered.');

    // Click reset button.
    $this->click('[data-drupal-selector="sm-snippet-reset"]');
    self::assertEquals($total_snippets, $this->countRows(), 'Snippets are not filtered.');
  }

  /**
   * Sets usage value.
   */
  protected function setFilterValue($filter, $value) {
    $page = $this->getSession()->getPage();
    $field = $page->find('css', sprintf('[data-drupal-selector="sm-snippet-%s"]', $filter));
    $field->setValue($value);
    if ($field->getTagName() == 'input') {
      if ($value == '') {
        // A trick to fire keyUp event.
        // See Drupal\Tests\views_ui\FunctionalJavascript\ViewsListingTest.
        $field->keyUp(1);
      }
      // Wait until Drupal.debounce() has fired the callback.
      usleep(150000);
    }
  }

  /**
   * Returns total number of visible rows.
   */
  protected function countRows() {
    $page = $this->getSession()->getPage();
    $rows = $page->findAll('css', '[data-drupal-selector="sm-snippet-list"] tbody tr:not(.empty-row)');
    $rows = array_filter($rows, function ($row) {
      /** @var \Behat\Mink\Element\NodeElement $row */
      return $row->isVisible();
    });
    return count($rows);
  }

  /**
   * Passes if empty row has a valid visibility.
   */
  protected function assertEmptyRow($visible) {
    $page = $this->getSession()->getPage();
    /** @var \Behat\Mink\Element\NodeElement $empty_row */
    $empty_row = $page->findAll('css', '.empty-row')[0];
    $visible ?
      $this->assertTrue($empty_row->isVisible(), 'Empty row is visible.') :
      $this->assertFalse($empty_row->isVisible(), 'Empty row is hidden.');
  }

}
