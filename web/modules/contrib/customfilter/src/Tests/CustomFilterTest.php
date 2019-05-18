<?php

// Namespace of tests.
namespace Drupal\customfilter\Tests;

// Use of base class for the tests.
use Drupal\simpletest\WebTestBase;

/**
 * Test the Custom Filter administration and use.
 *
 * @group customfilter
 */
class CustomFilterTest extends WebTestBase {
  // User with administrative permission on customfilter.
  protected $adminUser;

  // List of modules to enable.
  public static $modules = array('filter','customfilter', 'node');

  /**
   * Set up the tests and create the users.
   */
  public function setUp() {
    parent::setUp();

    // Create a content type, as we will create nodes on test.
    $settings = array(
      // Override default type (a random name).
      'type' => 'test',
      'name' => 'Test',
    );

    $this->drupalCreateContentType($settings);

    // Create a user with administrative privilegies.
    $this->adminUser = $this->drupalCreateUser(array(
      'administer permissions', 'administer customfilter',
      'administer filters', 'administer nodes', 'access administration pages',
      'create test content', 'edit any test content',
    ));

    $this->drupalLogin($this->adminUser);
  }

  /**
   * Create a new filter.
   */
  protected function createFilter() {
    $edit = array();
    $edit['id'] = 'test_filter';
    $edit['name'] = 'filter_name';
    $edit['cache'] = FALSE;
    $edit['description'] = 'filter description';
    $edit['shorttip'] = 'short tip';
    $edit['longtip'] = 'long tip';
    $this->drupalPostForm('admin/config/content/customfilter/add', $edit, t('Save'));
    $this->drupalGet('admin/config/content/customfilter');
    $this->assertText("filter_name", 'Filter created with sucess');
  }

  /**
   * Create a new rule.
   */
  protected function createRule() {
    $edit = array();
    $edit['rid'] = 'test_rule';
    $edit['name'] = 'Rule Name';
    $edit['description'] = 'rule description';
    $edit['enabled'] = TRUE;
    $edit['pattern'] = '/\[test\](.+)\[\/test\]/iUs';
    $edit['code'] = FALSE;
    $edit['replacement'] = '<b>$1</b>';
    $this->drupalPostForm('admin/config/content/customfilter/test_filter/add', $edit, t('Save'));
    $this->assertText("Rule Name", 'Rule created with sucess');
  }

  /**
   * Create a new subrule.
   */
  protected function createSubRule() {
    $edit = array();
    $edit['rid'] = 'test_subrule';
    $edit['name'] = 'Subrule Name';
    $edit['description'] = 'subrule description';
    $edit['enabled'] = TRUE;
    $edit['matches'] = '1';
    $edit['pattern'] = '/drupal/i';
    $edit['code'] = FALSE;
    $edit['replacement'] = '<font color="red">Drupal</font>';
    $this->drupalPostForm('admin/config/content/customfilter/test_filter/test_rule/add', $edit, t('Save'));
    $this->assertText("Subrule Name", 'Subrule created with sucess');
  }

  /**
   * Create a new text format.
   *
   * @param string $format_name
   *   The name of new text format.
   * @param array $filters
   *   Array with the machine names of filters to enable.
   */
  protected function createTextFormat($format_name, array $filters) {
    $edit = array();
    $edit['format'] = $format_name;
    $edit['name'] = $this->randomMachineName();
    $edit['roles[' . DRUPAL_AUTHENTICATED_RID . ']'] = 1;
    foreach ($filters as $filter) {
      $edit['filters[' . $filter . '][status]'] = TRUE;
    }
    $this->drupalPostForm('admin/config/content/formats/add', $edit, t('Save configuration'));
    $this->assertRaw(t('Added text format %format.', array('%format' => $edit['name'])), 'New filter created.');
    $this->drupalGet('admin/config/content/formats');
  }

  /**
   * Delete a filter.
   */
  protected function deleteFilter() {
    $edit = array();
    $this->drupalPostForm('admin/config/content/customfilter/test_filter/delete', $edit, t('Delete'));
  }

  /**
   * Delete a subrule.
   */
  protected function deleteSubRule() {
    $edit = array();
    $this->drupalPostForm('admin/config/content/customfilter/test_filter/test_rule/delete', $edit, t('Delete'));
  }

  /**
   * Edit an existing rule.
   */
  protected function editRule() {
    $edit = array();
    $edit['name'] = 'New rule label';
    $edit['description'] = 'rule description';
    $edit['enabled'] = TRUE;
    $edit['pattern'] = '/Goodbye Drupal 7/i';
    $edit['code'] = FALSE;
    $edit['replacement'] = 'Come back Drupal 7';
    $this->drupalPostForm('admin/config/content/customfilter/test_filter/test_rule/edit', $edit, t('Save'));
    $this->assertUrl('admin/config/content/customfilter/test_filter');
    $this->drupalGet('admin/config/content/customfilter/test_filter');
    // Test if there is a rule with new name.
    $this->assertRaw('New rule label', 'Updated rule with sucess.');
    // Test if do not exist a rule with previous name, so the rule is edited
    // and not inserted as a new one.
    $this->assertNoRaw('Rule Name', 'Previous rule do not exist.');
  }

  /**
   * Edit a existing subrule.
   */
  protected function editSubRule() {
    $edit = array();
    $edit['name'] = 'Renamed Subrule';
    $edit['description'] = 'New subrule description';
    $edit['matches'] = 1;
    $edit['pattern'] = '/Drupal/i';
    $edit['code'] = FALSE;
    $edit['replacement'] = '<font color="blue">Drupal</font>';
    $this->drupalPostForm('admin/config/content/customfilter/test_filter/test_subrule/edit', $edit, t('Save'));
    $this->assertText("Renamed Subrule", 'Subrule edited with sucess');
    $this->assertUrl('admin/config/content/customfilter/test_filter');
    // Test if there is a rule with new name.
    $this->assertRaw('Renamed Subrule', 'Updated rule with sucess.');
    // Test if do not exist a rule with previous name, so the rule is edited
    // and not inserted as a new one.
    $this->assertNoRaw('Subrule Name', 'Previous rule do not exist.');
  }

  /**
   * Test if node content is replaced by rule.
   */
  protected function ruleContent() {
    $this->drupalGet('node/1');
    $this->assertRaw('Drupal <b>Goodbye <font color="red">Drupal</font> 7</b>', 'Node content is replaced');
  }

  /**
   * Run all the tests.
   */
  public function testModule() {
    // Test create filter.
    $this->createFilter();

    // Test create rule.
    $this->createRule();

    // Create a subrule.
    $this->createSubRule();

    // Test create a new text format with your filter enabled.
    $this->createTextFormat('customfilter', array('customfilter_test_filter'));

    // Create a node.
    $node = array(
      'title' => 'Test for Custom Filter',
      'body' => array(
        array(
          'value' => 'Drupal [test]Goodbye Drupal 7[/test]',
          'format' => 'customfilter',
        ),
      ),
      'type' => 'test',
    );
    $this->drupalCreateNode($node);

    // Test the node content with the rule.
    $this->ruleContent();

    // Edit the rule.
    $this->editRule();

    // Edit the sub rule.
    $this->editSubRule();

    // Delete the sub rule.
    $this->deleteSubRule();

    // Delete the filter.
    $this->deleteFilter();
  }

  /**
   * Test if CustomFilter shows on admin/config.
   *
   * This test is for https://drupal.org/node/2143991.
   */
  public function testAdminPage() {
    $this->drupalGet('admin/config');
    // Assert for module.
    $this->assertRaw('Custom Filter', 'Custom Filter is in admin/config');
    // Assert for description.
    $this->assertRaw('Create and manage your own custom filters.',
      'Description is in admin/config');
    $this->assertLinkByHref('admin/config/content/customfilter',
      0,
      'A link to custom filter is on the page.');
  }
}
