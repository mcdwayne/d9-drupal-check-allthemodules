<?php

namespace Drupal\pbf\Tests;

/**
 * Test access permissions with Pbf field which reference role.
 *
 * @group pbf
 */
class PbfTestAccessByRole extends PbfTestBase {

  /*
   * Field name to add.
   *
   * @var string
   */
  protected $fieldname;


  /**
   * Setup and create content whith Pbf field.
   */
  public function setUp() {
    parent::setUp();

    $this->fieldname = 'field_pbf_role';
    $this->attachPbfRoleFields($this->fieldname);

    $this->user = $this->drupalCreateUser($this->permissions);

    $this->article1 = $this->createSimpleArticle('Article 1', $this->fieldname, $this->role1, 1, 0, 0, 0);
    $this->article2 = $this->createSimpleArticle('Article 2', $this->fieldname, $this->role1, 0, 1, 0, 0);

  }

  /**
   * Test the "pbf" node access with a Pbf field which reference role.
   *
   * - Test access with standard permissions.
   * - Access to each node
   * - Search the node and count result.
   */
  protected function testPbfAccessByRole() {

    $this->drupalLogin($this->adminUser);

    $this->drupalGet("node/{$this->article1->id()}");
    $this->assertResponse(200, 'adminUser is allowed to view the content.');
    $this->drupalGet("node/{$this->article1->id()}/edit");
    // Make sure we don't get a 401 unauthorized response:
    $this->assertResponse(200, 'adminUser is allowed to edit the content.');

    $this->drupalGet("node/{$this->article2->id()}");
    $this->assertResponse(200, 'adminUser is allowed to view the content.');
    $this->drupalGet("node/{$this->article2->id()}/edit");
    // Make sure we don't get a 401 unauthorized response:
    $this->assertResponse(200, 'adminUser is allowed to edit the content.');

    $bundle_path = 'admin/structure/types/manage/article';
    // Check that the field appears in the overview form.
    $this->drupalGet($bundle_path . '/fields');
    $this->assertFieldByXPath('//table[@id="field-overview"]//tr[@id="field-pbf-role"]/td[1]', 'Grant access to role', 'Field was created and appears in the overview page.');

    // Check that the field appears in the overview manage display form.
    $this->drupalGet($bundle_path . '/form-display');
    $this->assertFieldByXPath('//table[@id="field-display-overview"]//tr[@id="field-pbf-role"]/td[1]', 'Grant access to role', 'Field appears in the Manage form display page.');
    $this->assertFieldByName('fields[field_pbf_role][type]', 'pbf_widget', 'The expected widget is selected.');

    // Check that the field appears in the overview manage display page.
    $this->drupalGet($bundle_path . '/display');
    $this->assertFieldByXPath('//table[@id="field-display-overview"]//tr[@id="field-pbf-role"]/td[1]', 'Grant access to role', 'Field appears in the Manage form display page.');
    $this->assertFieldByName('fields[field_pbf_role][type]', 'pbf_formatter_default', 'The expected formatter is selected.');

    // Test view access with normal user.
    $this->drupalLogin($this->normalUser);
    $this->drupalGet("node/{$this->article2->id()}");
    $this->assertText(t('Access denied'));
    $this->assertResponse(403);
    $this->drupalGet("node/{$this->article1->id()}");
    $this->assertResponse(200);

    $this->drupalGet("node/{$this->article1->id()}/edit");
    $this->assertResponse(403);
    $this->drupalGet("node/{$this->article2->id()}/edit");
    $this->assertResponse(403);

    // Build the search index.
    $this->cronRun();
    // Check to see that we find the number of search results expected.
    $this->checkSearchResults('Article', 1);

    // Add role1 to normalUser.
    $this->normalUser->addRole($this->role1);
    $this->normalUser->save();

    // Check search.
    $this->cronRun();
    $this->checkSearchResults('Article', 2);
    // Check view.
    $this->drupalGet("node/{$this->article2->id()}");
    $this->assertResponse(200);
    // Check edit.
    $this->drupalGet("node/{$this->article2->id()}/edit");
    $this->assertResponse(403);
    // Check delete.
    $this->drupalGet("node/{$this->article2->id()}/delete");
    $this->assertResponse(403);

    // Set article2 with view, update, delete permissions.
    $value = [
      'target_id' => $this->role1,
      'grant_public' => 0,
      'grant_view' => 1,
      'grant_update' => 1,
      'grant_delete' => 1,
    ];
    $this->article2->set($this->fieldname, $value)->save();
    // Check view.
    $this->drupalGet("node/{$this->article2->id()}");
    $this->assertResponse(200);
    // Check edit.
    $this->drupalGet("node/{$this->article2->id()}/edit");
    $this->assertResponse(200);
    // Check delete.
    $this->drupalGet("node/{$this->article2->id()}/delete");
    $this->assertResponse(200);

    // Test with anonymous user.
    $this->drupalLogout();
    $this->drupalGet("node/{$this->article1->id()}");
    $this->assertResponse(200);
    $this->drupalGet("node/{$this->article2->id()}");
    $this->assertResponse(403);

    // Test with user.
    $this->drupalLogin($this->user);
    $this->drupalGet("node/{$this->article1->id()}");
    $this->assertResponse(200);
    $this->drupalGet("node/{$this->article2->id()}");
    $this->assertResponse(403);

  }

}
