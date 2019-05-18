<?php

namespace Drupal\pbf\Tests;

/**
 * Test access permissions with Pbf field which reference term.
 *
 * @group pbf
 */
class PbfTestAccessByTermRef extends PbfTestBase {

  /*
   * Field name to add.
   *
   * @var string
   */
  protected $fieldname;

  /**
   * Setup and create content with Pbf field.
   */
  public function setUp() {
    parent::setUp();
    $this->fieldname = 'field_pbf_term';
    $this->attachPbfTermFields($this->vocabulary, $this->fieldname);

    $this->article1 = $this->createSimpleArticle('Article 1', $this->fieldname, $this->term1->id(), 1, 0, 0, 0);
    $this->article2 = $this->createSimpleArticle('Article 2', $this->fieldname, $this->term1->id(), 0, 1, 0, 0);

  }

  /**
   * Test the "pbf" node access with a Pbf field which reference term.
   *
   * - Test access with standard permissions.
   * - Access to each node
   * - Search the node and count result.
   */
  protected function testPbfAccessByTermRef() {

    $this->drupalLogin($this->adminUser);

    $this->drupalGet("node/{$this->article1->id()}");
    $this->assertResponse(200, 'adminUser is allowed to view the content.');
    $this->drupalGet("node/{$this->article1->id()}/edit");
    // Make sure we don't get a 401 unauthorized response:
    $this->assertResponse(200, 'adminUser is allowed to edit the content.');

    $bundle_path = 'admin/structure/types/manage/article';
    // Check that the field appears in the overview form.
    $this->drupalGet($bundle_path . '/fields');
    $this->assertFieldByXPath('//table[@id="field-overview"]//tr[@id="field-pbf-term"]/td[1]', 'Content related to term', 'Field was created and appears in the overview page.');

    // Check that the field appears in the overview manage display form.
    $this->drupalGet($bundle_path . '/form-display');
    $this->assertFieldByXPath('//table[@id="field-display-overview"]//tr[@id="field-pbf-term"]/td[1]', 'Content related to term', 'Field appears in the Manage form display page.');
    $this->assertFieldByName('fields[field_pbf_term][type]', 'pbf_widget', 'The expected widget is selected.');

    // Check that the field appears in the overview manage display page.
    $this->drupalGet($bundle_path . '/display');
    $this->assertFieldByXPath('//table[@id="field-display-overview"]//tr[@id="field-pbf-term"]/td[1]', 'Content related to term', 'Field appears in the Manage form display page.');
    $this->assertFieldByName('fields[field_pbf_term][type]', 'pbf_formatter_default', 'The expected formatter is selected.');

    $user_path_config = 'admin/config/people/accounts';
    $this->drupalGet($user_path_config . '/fields');
    $this->assertFieldByXPath('//table[@id="field-overview"]//tr[@id="field-pbf-term"]/td[1]', 'User related to term', 'User Pbf field was created and appears in the overview page.');
    $this->drupalGet($user_path_config . '/form-display');
    $this->assertFieldByName('fields[field_pbf_term][type]', 'pbf_widget', 'The expected widget is selected.');
    $this->drupalGet($user_path_config . '/display');
    $this->assertFieldByName('fields[field_pbf_term][type]', 'pbf_formatter_default', 'The expected formatter is selected.');

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

    // Set article2 as public without custom permission.
    $value = [
      'target_id' => $this->term1->id(),
      'grant_public' => 1,
      'grant_view' => 0,
      'grant_update' => 0,
      'grant_delete' => 0,
    ];
    $this->article2->set($this->fieldname, $value)->save();
    $this->drupalGet("node/{$this->article2->id()}");
    $this->assertResponse(200);
    $this->checkSearchResults('Article', 2);

    $this->drupalGet("node/{$this->article2->id()}/edit");
    $this->assertResponse(403);

    $this->drupalGet("node/{$this->article2->id()}/delete");
    $this->assertResponse(403);

    // Set article2 with view permission.
    $value = [
      'target_id' => $this->term1->id(),
      'grant_public' => 0,
      'grant_view' => 1,
      'grant_update' => 0,
      'grant_delete' => 0,
    ];
    $this->article2->set($this->fieldname, $value)->save();
    $this->drupalGet("node/{$this->article2->id()}");
    $this->assertResponse(403);
    $this->checkSearchResults('Article', 1);

    // Associate normalUser with term1.
    $this->setUserField($this->normalUser->id(), $this->fieldname, ['target_id' => $this->term1->id()]);

    // Check if user is well associated with group1.
    $this->drupalGet("user/{$this->normalUser->id()}/edit");
    $this->assertResponse(200);
    $this->assertFieldByName('field_pbf_term[0][target_id]', $this->term1->getName() . ' (' . $this->term1->id() . ')', 'The expected value is found in the Pbf input field');
    $this->drupalGet("user/{$this->normalUser->id()}");
    $this->assertLink($this->term1->getName());
    $this->assertResponse(200);

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
      'target_id' => $this->term1->id(),
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

  }

}
