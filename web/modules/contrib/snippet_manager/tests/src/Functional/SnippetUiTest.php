<?php

namespace Drupal\Tests\snippet_manager\Functional;

/**
 * Tests the Snippet manager user interface.
 *
 * @group snippet_manager
 */
class SnippetUiTest extends TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['views'];

  /**
   * Tests snippet variable form.
   */
  public function testSnippetVariableForm() {

    $this->drupalGet('admin/structure/snippet/alpha/edit/template');
    $this->assertXpath('//a[text() = "Add variable" and contains(@href, "/admin/structure/snippet/alpha/edit/variable/add")]');

    $this->drupalGet('/admin/structure/snippet/alpha/edit/variable/add');
    $this->assertPageTitle('Add variable');
    $this->assertXpath('//label[text() = "Type of the variable"]');

    $select_prefix = '//select[@name="plugin_id" and @required]';
    $select_axes[] = '/option[@selected and text()="- Select -"]';
    $select_axes[] = '/optgroup[@label="Condition"]/option[@value="condition:request_path" and text()="Request Path"]';
    $select_axes[] = '/optgroup[@label="Condition"]/option[@value="condition:current_theme" and text()="Current Theme"]';
    $select_axes[] = '/optgroup[@label="Condition"]/option[@value="condition:user_role" and text()="User Role"]';
    $select_axes[] = '/optgroup[@label="Entity"]/option[@value="entity:snippet" and text()="Snippet"]';
    $select_axes[] = '/optgroup[@label="Entity"]/option[@value="entity:user" and text()="User"]';
    $select_axes[] = '/optgroup[@label="Other"]/option[@value="text" and text()="Formatted text"]';
    $select_axes[] = '/optgroup[@label="Other"]/option[@value="url" and text()="Url"]';
    $select_axes[] = '/optgroup[@label="Other"]/option[@value="file" and text()="File"]';
    $select_axes[] = '/optgroup[@label="View"]/option[@value="view:user_admin_people" and text()="People"]';
    $select_axes[] = '/optgroup[@label="View"]/option[@value="view:who_s_new" and text()="Who\'s new"]';
    $select_axes[] = '/optgroup[@label="View"]/option[@value="view:who_s_online" and text()="Who\'s online block"]';
    $this->assertXpaths($select_axes, $select_prefix);

    $this->assertXpath('//label[text() = "Name of the variable"]');
    $this->assertXpath('//input[@name="name" and @required]');

    $edit = [
      'plugin_id' => 'text',
      'name' => 'bar',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save and continue'));

    $this->assertPageTitle(t('Edit variable %variable', ['%variable' => $edit['name']]));
    $this->assertSession()->addressEquals('/admin/structure/snippet/alpha/edit/variable/bar/edit');

    $this->drupalGet('admin/structure/snippet/alpha/edit/template');

    $expected_header = [
      'Name',
      'Type',
      'Plugin',
      'Operations',
    ];
    foreach ($this->xpath('//main//table//th') as $key => $th) {
      self::assertEquals($expected_header[$key], $th->getText(), 'Valid table header was found.');
    }

    $variable_row_prefix = '//main//table/tbody/tr';
    $variable_axes[] = '/td[position() = 1]/a[@href="#snippet-edit-form" and text() = "bar"]';
    $variable_axes[] = '/td[position() = 2 and text() = "String"]';
    $variable_axes[] = '/td[position() = 3 and text() = "text"]';
    $this->assertXpaths($variable_axes, $variable_row_prefix);

    $link_prefix = $variable_row_prefix . '/td[position() = 4]//ul[@class="dropbutton"]/li';
    $link_axes[] = '/a[contains(@href, "/snippet/alpha/edit/variable/bar/edit") and text() = "Edit"]';
    $link_axes[] = '/a[contains(@href, "/snippet/alpha/edit/variable/bar/delete") and text() = "Delete"]';
    $this->assertXpaths($link_axes, $link_prefix);

    $this->drupalGet('/admin/structure/snippet/alpha/edit/variable/not-existing-variable/edit');
    $this->assertSession()->statusCodeEquals(404);
  }

  /**
   * Tests snippet variable delete form.
   */
  public function testSnippetVariableDeleteForm() {
    $this->drupalGet('/admin/structure/snippet/alpha/edit/variable/foo/delete');
    $this->assertPageTitle(t('Are you sure you want to delete the variable %variable?', ['%variable' => 'foo']));
    $this->assertSession()->pageTextContains('This action cannot be undone.');
    $this->assertXpath('//a[text() = "Cancel" and contains(@href, "/admin/structure/snippet/alpha/edit")]');
    $this->drupalPostForm(NULL, [], t('Delete'));
    $this->assertStatusMessage(t('The variable has been removed.'));
    $this->assertSession()->addressEquals('/admin/structure/snippet/alpha/edit/template');
    $this->assertXpath('//table//td[text() = "Variables are not configured yet."]');
  }

}
