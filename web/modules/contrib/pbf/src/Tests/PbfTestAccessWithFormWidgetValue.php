<?php

namespace Drupal\pbf\Tests;

/**
 * Test access permissions with Pbf field which reference node.
 *
 * Grants are set on the widget form settings.
 *
 * @group pbf
 */
class PbfTestAccessWithFormWidgetValue extends PbfTestBase {

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

    $this->fieldname = 'field_pbf_group';
    $this->attachPbfNodeFields($this->fieldname);

    // Article 1 is public. Article 2 is private (view only).
    $this->article1 = $this->createSimpleArticle('Article 1', $this->fieldname, $this->group1->id(), 1, 0, 0, 0);
    $this->article2 = $this->createSimpleArticle('Article 2', $this->fieldname, $this->group1->id(), 0, 1, 0, 0);

  }

  /**
   * Test the pbf node access with a Pbf field with grants value from widget.
   */
  protected function testPbfAccessWithFormWidget() {

    $this->drupalLogin($this->adminUser);

    $this->drupalGet("node/{$this->article1->id()}");
    $this->assertResponse(200, 'adminUser is allowed to view the content.');
    $this->drupalGet("node/{$this->article1->id()}/edit");
    // Make sure we don't get a 401 unauthorized response:
    $this->assertResponse(200, 'adminUser is allowed to edit the content.');

    $bundle_path = 'admin/structure/types/manage/article';
    // Check that the field appears in the overview form.
    $this->drupalGet($bundle_path . '/fields');
    $this->assertFieldByXPath('//table[@id="field-overview"]//tr[@id="field-pbf-group"]/td[1]', 'Content of group', 'Field was created and appears in the overview page.');

    // Check that the field appears in the overview manage display form.
    $this->drupalGet($bundle_path . '/form-display');
    $this->assertFieldByXPath('//table[@id="field-display-overview"]//tr[@id="field-pbf-group"]/td[1]', 'Content of group', 'Field appears in the Manage form display page.');
    $this->assertFieldByName('fields[field_pbf_group][type]', 'pbf_widget', 'The expected widget is selected.');
    // Check that grants are not set with the form widget settings.
    $this->assertText(t('Grants access set on each node. Default grant access are :'));

    // Check that the field appears in the overview manage display page.
    $this->drupalGet($bundle_path . '/display');
    $this->assertFieldByXPath('//table[@id="field-display-overview"]//tr[@id="field-pbf-group"]/td[1]', 'Content of group', 'Field appears in the Manage form display page.');
    $this->assertFieldByName('fields[field_pbf_group][type]', 'pbf_formatter_default', 'The expected formatter is selected.');

    $user_path_config = 'admin/config/people/accounts';
    $this->drupalGet($user_path_config . '/fields');
    $this->assertFieldByXPath('//table[@id="field-overview"]//tr[@id="field-pbf-group"]/td[1]', 'Member of group', 'User Obf field was created and appears in the overview page.');
    $this->drupalGet($user_path_config . '/form-display');
    $this->assertFieldByName('fields[field_pbf_group][type]', 'pbf_widget', 'The expected widget is selected.');
    $this->drupalGet($user_path_config . '/display');
    $this->assertFieldByName('fields[field_pbf_group][type]', 'pbf_formatter_default', 'The expected formatter is selected.');

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

    // Change settings in the widget form. Now Articles must be all public.
    $settings = [
      'match_operator' => 'CONTAINS',
      'size' => 30,
      'placeholder' => '',
      'grant_global' => 1,
      'grant_public' => 1,
      'grant_view' => 0,
      'grant_update' => 0,
      'grant_delete' => 0,
    ];
    $this->setFormDisplay('node.article.default', 'node', 'article', 'default', $this->fieldname, 'pbf_widget', $settings);
    // Check that grants are set generally on the form widget settings.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($bundle_path . '/form-display');
    $this->assertText(t('Grants access set generally. Grant access used are :'));

    // Save articles for acquire new rights access.
    $edit = [
      $this->fieldname . '[0][target_id]' => $this->group1->getTitle() . ' (' . $this->group1->id() . ')',
    ];
    $this->drupalPostForm('/node/' . $this->article1->id() . '/edit', $edit, t('Save'));
    $this->assertText(t('has been updated'));
    $this->assertResponse(200);
    $this->drupalGet("node/{$this->article1->id()}");
    $this->assertLink($this->group1->getTitle());

    $edit = [
      $this->fieldname . '[0][target_id]' => $this->group1->getTitle() . ' (' . $this->group1->id() . ')',
    ];
    $this->drupalPostForm('/node/' . $this->article2->id() . '/edit', $edit, t('Save'));
    $this->assertText(t('has been updated'));
    $this->assertResponse(200);
    $this->drupalGet("node/{$this->article1->id()}");
    $this->assertLink($this->group1->getTitle());

    $this->drupalLogin($this->normalUser);
    $this->drupalGet("node/{$this->article2->id()}");
    $this->assertResponse(200);
    $this->drupalGet("node/{$this->article2->id()}/edit");
    $this->assertResponse(403);
    $this->drupalGet("node/{$this->article2->id()}/delete");
    $this->assertResponse(403);

    // Nothing has changed for article 1.
    $this->drupalGet("node/{$this->article1->id()}");
    $this->assertResponse(200);
    $this->drupalGet("node/{$this->article1->id()}/edit");
    $this->assertResponse(403);
    $this->drupalGet("node/{$this->article1->id()}/delete");
    $this->assertResponse(403);
    // Search content Article. Must find 2 articles.
    $this->checkSearchResults('Article', 2);

    // Change settings in the widget form. Now Articles must be all private.
    $settings = [
      'match_operator' => 'CONTAINS',
      'size' => 30,
      'placeholder' => '',
      'grant_global' => 1,
      'grant_public' => 0,
      'grant_view' => 1,
      'grant_update' => 0,
      'grant_delete' => 0,
    ];
    $this->setFormDisplay('node.article.default', 'node', 'article', 'default', $this->fieldname, 'pbf_widget', $settings);
    // Check that grants are set generally on the form widget settings.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($bundle_path . '/form-display');
    $this->assertText(t('Grants access set generally. Grant access used are :'));
    $this->assertText(t('Grant view:1'));

    // Save articles for acquire new rights access.
    $edit = [
      $this->fieldname . '[0][target_id]' => $this->group1->getTitle() . ' (' . $this->group1->id() . ')',
    ];
    $this->drupalPostForm('/node/' . $this->article1->id() . '/edit', $edit, t('Save'));
    $this->assertText(t('has been updated'));
    $this->assertResponse(200);
    $this->drupalGet("node/{$this->article1->id()}");
    $this->assertLink($this->group1->getTitle());

    $edit = [
      $this->fieldname . '[0][target_id]' => $this->group1->getTitle() . ' (' . $this->group1->id() . ')',
    ];
    $this->drupalPostForm('/node/' . $this->article2->id() . '/edit', $edit, t('Save'));
    $this->assertText(t('has been updated'));
    $this->assertResponse(200);
    $this->drupalGet("node/{$this->article1->id()}");
    $this->assertLink($this->group1->getTitle());

    $this->drupalLogin($this->normalUser);

    $this->drupalGet("node/{$this->article2->id()}");
    $this->assertResponse(403);
    $this->drupalGet("node/{$this->article2->id()}/edit");
    $this->assertResponse(403);
    $this->drupalGet("node/{$this->article2->id()}/delete");
    $this->assertResponse(403);

    // Article 1 must be private now..
    $this->drupalGet("node/{$this->article1->id()}");
    $this->assertResponse(403);
    $this->drupalGet("node/{$this->article1->id()}/edit");
    $this->assertResponse(403);
    $this->drupalGet("node/{$this->article1->id()}/delete");
    $this->assertResponse(403);

    $this->checkSearchResults('Article', 0);

    // Associate normalUser with group1.
    $this->setUserField($this->normalUser->id(), $this->fieldname, ['target_id' => $this->group1->id()]);

    // Check if user is well associated with group1.
    $this->drupalGet("user/{$this->normalUser->id()}/edit");
    $this->assertResponse(200);
    $this->assertFieldByName('field_pbf_group[0][target_id]', $this->group1->getTitle() . ' (' . $this->group1->id() . ')', 'The expected value is found in the Pbf input field');
    $this->drupalGet("user/{$this->normalUser->id()}");
    $this->assertLink($this->group1->getTitle());
    $this->assertResponse(200);

    // Check search.
    $this->cronRun();
    $this->checkSearchResults('Article', 2);
    // Check view.
    $this->drupalGet("node/{$this->article2->id()}");
    $this->assertResponse(200);
    $this->drupalGet("node/{$this->article1->id()}");
    $this->assertResponse(200);
    // Check edit.
    $this->drupalGet("node/{$this->article2->id()}/edit");
    $this->assertResponse(403);
    $this->drupalGet("node/{$this->article1->id()}/edit");
    $this->assertResponse(403);
    // Check delete.
    $this->drupalGet("node/{$this->article2->id()}/delete");
    $this->assertResponse(403);
    $this->drupalGet("node/{$this->article1->id()}/delete");
    $this->assertResponse(403);

    // Change settings in the widget form. Now Articles must be all private
    // with all rights access.
    $settings = [
      'match_operator' => 'CONTAINS',
      'size' => 30,
      'placeholder' => '',
      'grant_global' => 1,
      'grant_public' => 0,
      'grant_view' => 1,
      'grant_update' => 1,
      'grant_delete' => 1,
    ];
    $this->setFormDisplay('node.article.default', 'node', 'article', 'default', $this->fieldname, 'pbf_widget', $settings);
    // Check that grants are set generally on the form widget settings.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($bundle_path . '/form-display');
    $this->assertText(t('Grants access set generally. Grant access used are :'));
    $this->assertText(t('Grant update:1'));
    $this->assertText(t('Grant delete:1'));

    // Save articles for acquire new rights access.
    $edit = [
      $this->fieldname . '[0][target_id]' => $this->group1->getTitle() . ' (' . $this->group1->id() . ')',
    ];
    $this->drupalPostForm('/node/' . $this->article1->id() . '/edit', $edit, t('Save'));
    $this->assertText(t('has been updated'));
    $this->assertResponse(200);
    $this->drupalGet("node/{$this->article1->id()}");
    $this->assertLink($this->group1->getTitle());

    $edit = [
      $this->fieldname . '[0][target_id]' => $this->group1->getTitle() . ' (' . $this->group1->id() . ')',
    ];
    $this->drupalPostForm('/node/' . $this->article2->id() . '/edit', $edit, t('Save'));
    $this->assertText(t('has been updated'));
    $this->assertResponse(200);
    $this->drupalGet("node/{$this->article2->id()}");
    $this->assertLink($this->group1->getTitle());

    $this->drupalLogin($this->normalUser);

    // Check view.
    $this->drupalGet("node/{$this->article2->id()}");
    $this->assertResponse(200);
    $this->drupalGet("node/{$this->article1->id()}");
    $this->assertResponse(200);
    // Check edit.
    $this->drupalGet("node/{$this->article2->id()}/edit");
    $this->assertResponse(200);
    $this->drupalGet("node/{$this->article1->id()}/edit");
    $this->assertResponse(200);
    // Check delete.
    $this->drupalGet("node/{$this->article2->id()}/delete");
    $this->assertResponse(200);
    $this->drupalGet("node/{$this->article1->id()}/delete");
    $this->assertResponse(200);

    // Test with anonymous user.
    $this->drupalLogout();
    $this->drupalGet("node/{$this->article1->id()}");
    $this->assertResponse(403);
    $this->drupalGet("node/{$this->article2->id()}");
    $this->assertResponse(403);
    // Check edit.
    $this->drupalGet("node/{$this->article2->id()}/edit");
    $this->assertResponse(403);
    $this->drupalGet("node/{$this->article1->id()}/edit");
    $this->assertResponse(403);
    // Check delete.
    $this->drupalGet("node/{$this->article2->id()}/delete");
    $this->assertResponse(403);
    $this->drupalGet("node/{$this->article1->id()}/delete");
    $this->assertResponse(403);

  }

}
