<?php

namespace Drupal\Tests\snippet_manager\Functional;

/**
 * Tests URL variable plugin.
 *
 * @group snippet_manager
 */
class UrlVariableTest extends TestBase {

  /**
   * Test callback.
   */
  public function testUrlVariable() {

    $path = 'node/1?page=1#title';

    $edit = [
      'plugin_id' => 'url',
      'name' => 'url',
    ];
    $this->drupalPostForm('admin/structure/snippet/alpha/edit/variable/add', $edit, 'Save and continue');

    $this->assertStatusMessage('The variable has been created.');

    $edit = [
      'configuration[path]' => '/' . $path,
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    $this->assertStatusMessage('The variable has been updated.');

    $this->drupalGet('admin/structure/snippet/alpha/edit/template');
    $this->assertXpath('//main//table/tbody/tr/td[position() = 1]/a[@href="#snippet-edit-form" and text() = "url"]');

    $edit = [
      'template[value]' => '<div class="snippet-url">{{ url }}</div>',
    ];
    $this->drupalPostForm('admin/structure/snippet/alpha/edit/template', $edit, 'Save');

    $this->drupalGet('admin/structure/snippet/alpha');
    $this->assertXpath(sprintf('//div[@class="snippet-url" and text() = "%s"]', base_path() . $path));

    // Test path validation.
    $edit = [
      'plugin_id' => 'url',
      'name' => 'wrong_url',
    ];
    $this->drupalPostForm('admin/structure/snippet/alpha/edit/variable/add', $edit, 'Save and continue');

    $edit = [
      'configuration[path]' => $path,
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertErrorMessage('The path should begin with "/".');

    // Make sure that empty path does not produce PHP exception.
    $this->drupalGet('admin/structure/snippet/alpha');
  }

  /**
   * Test callback.
   */
  public function testAutocompletePath() {
    $response = $this->drupalGet('admin/structure/snippet/path-autocomplete', ['query' => ['q' => '/user/pass']]);
    $suggestions = json_decode($response);
    self::assertEquals(count($suggestions), 1);
    self::assertEquals($suggestions[0]->value, '/user/password');
    self::assertEquals($suggestions[0]->label, '/user/password');
  }

}
