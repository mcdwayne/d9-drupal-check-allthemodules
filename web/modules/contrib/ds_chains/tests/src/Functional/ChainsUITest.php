<?php

namespace Drupal\Tests\ds_chains\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\ds_chains\Kernel\ChainedFieldTestTrait;

/**
 * Defines a class for testing display suite chains UI.
 *
 * @group ds_chains
 */
class ChainsUITest extends BrowserTestBase {

  use ChainedFieldTestTrait;
  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $admin;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'ds',
    'layout_discovery',
    'ds_chains',
    'field_ui',
    'user',
    'text',
    'field',
    'system',
    'field_test',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->createTestField('test_field', 'Some field', 'user', 'user', 'test_field');
    $this->createTestField('test_other_field', 'Some other field', 'user', 'user', 'test_field');
    $this->admin = $this->createUser([], NULL, TRUE);
    $this->admin->test_field = 1234;
    $this->admin->save();
    $this->drupalLogin($this->admin);
    $this->createContentType(['type' => 'page']);
  }

  /**
   * Test UI.
   */
  public function testUi() {
    $this->assertChainedFieldsNotAvailableWithoutDsLayout();
    $this->assertEnableDsDisplayForPageContentType();
    $this->assertEnableChainingForFields(['uid']);
    $this->assertFormatterOptionsForField();
    $this->assertConfigureOptionsForField();
    $this->assertChainedFieldsAreOutput();
    $this->assertEmptyChainedFieldsAreNotOutput();
  }

  /**
   * Enable DS display for page content type.
   */
  protected function assertEnableDsDisplayForPageContentType() {
    $this->drupalGet('admin/structure/types/manage/page/display');
    $this->submitForm([
      'layout' => 'ds_1col',
    ], 'Save');
  }

  /**
   * Enable chaining for fields.
   *
   * @param array $field_names
   *   Field names.
   */
  protected function assertEnableChainingForFields(array $field_names) {
    $fields = [];
    foreach ($field_names as $field_name) {
      $fields["ds_chains[fields][$field_name]"] = 1;
    }
    $this->submitForm($fields, 'Save');
  }

  /**
   * Assert that no chain fields until DS layout is enabled.
   */
  protected function assertChainedFieldsNotAvailableWithoutDsLayout() {
    $this->drupalGet('admin/structure/types/manage/page/display');
    $this->assertSession()->fieldNotExists('ds_chains[fields][uid]');
  }

  /**
   * Assert that formatter options exist.
   */
  protected function assertFormatterOptionsForField() {
    $this->submitForm([
      'fields[ds_chains:node/page/uid/test_field][label]' => 'above',
      'fields[ds_chains:node/page/uid/test_field][plugin][type]' => 'field_test_default',
      'fields[ds_chains:node/page/uid/test_field][region]' => 'ds_content',
      'fields[ds_chains:node/page/uid/test_other_field][label]' => 'above',
      'fields[ds_chains:node/page/uid/test_other_field][plugin][type]' => 'field_test_default',
      'fields[ds_chains:node/page/uid/test_other_field][region]' => 'ds_content',
    ], 'Save');
  }

  /**
   * Assert configure options for field.
   */
  protected function assertConfigureOptionsForField() {
    $page = $this->getSession()->getPage();
    $page->findButton('ds_chains:node/page/uid/test_field_plugin_settings_edit')->press();
    $page->fillField('fields[ds_chains:node/page/uid/test_field][settings_edit_form][settings][test_formatter_setting]', 'PONIES');
    $page->findButton('ds_chains:node/page/uid/test_field_plugin_settings_update')->press();
    $this->assertSession()->pageTextContains('test_formatter_setting: PONIES');
    $this->submitForm([], 'Save');
  }

  /**
   * Assert that chained fields are output.
   */
  protected function assertChainedFieldsAreOutput() {
    $this->drupalGet('/node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Some node',
    ], 'Save');
    $this->assertSession()->pageTextContains('PONIES|1234');
  }

  /**
   * Assert that empty fields are not output.
   */
  protected function assertEmptyChainedFieldsAreNotOutput() {
    $this->drupalGet('/node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Some other node',
    ], 'Save');
    $assert = $this->assertSession();
    $assert->pageTextContains('Some field');
    $assert->pageTextNotContains('Some other field');
  }

}
