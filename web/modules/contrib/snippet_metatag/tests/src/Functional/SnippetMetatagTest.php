<?php

namespace Drupal\Tests\snippet_metatag\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test for snippet metatags.
 *
 * @group snippet_metatag
 */
class SnippetMetatagTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['snippet_metatag'];

  /**
   * Test callback.
   */
  public function testContentPage() {
    $admin_user = $this->drupalCreateUser(['administer snippets']);
    $this->drupalLogin($admin_user);

    $edit = [
      'label' => 'Test',
      'id' => 'test',
      'page[status]' => TRUE,
      'page[path]' => 'test',
    ];
    $this->drupalPostForm('admin/structure/snippet/add', $edit, 'Save');

    $edit = [
      'title' => 'New snippet title',
    ];
    $this->drupalPostForm('admin/structure/snippet/test/edit/metatag', $edit, 'Save');
    $this->assertSession()->responseContains('Snippet <em class="placeholder">Test</em> has been updated.');
    $this->assertSession()->elementExists('xpath', '//input[@name = "title" and @value = "New snippet title"]');

    $this->drupalGet('test');
    $this->assertSession()->elementExists('xpath', '//title[text() = "New snippet title"]');

    $this->drupalPostForm('admin/structure/snippet/test/edit/metatag', [], 'Reset');
    $this->assertSession()->responseContains('Snippet <em class="placeholder">Test</em> has been updated.');
    $this->assertSession()->elementExists('xpath', '//input[@name = "title" and @value = ""]');

    $this->drupalGet('test');
    $this->assertSession()->elementExists('xpath', '//title[text() = "Test | Drupal"]');
  }

}
