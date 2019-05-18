<?php
/**
 * @file tests
 * Tests for forena.
 * @author davidmetzler
 *
 */
namespace Drupal\forena\Tests;
use Drupal\forena\AppService;
use Drupal\forena\Frx;
use Drupal\simpletest\WebTestBase;

/**
 * Class ForenaInegrationTest
 *
 * @group Forena
 * @ingroup Forena
 */
class ForenaInegrationTest extends WebTestBase {
  public $privileged_user;
  public $report_user;
  public static $modules = ['filter', 'forena', 'forena_test'];
  protected $profile = 'minimal';
  /** @var  \Drupal\forena\Frx */
  protected $frx;

  public function __construct($test_id) {
    parent::__construct($test_id);
    $this->frx = Frx::instance();
  }

  public static function getInfo() {

    return array(
      'name' => 'Forena Reports',
      'description' => 'Report rendering tests for forena',
      'group' => t('Forena'),
    );
  }

  public function setup() {
    parent::setUp();
    // Create and log in our privileged user.
    $this->privileged_user = $this->drupalCreateUser(
      [
        'administer forena',
        'list forena reports',
      ],
      'test_admin',
      TRUE
    );
    $this->report_user = $this->drupalCreateUser(
      [
        'list forena reports',
      ],
      'test_user',
      TRUE
    );
  }

  /**
   * Test the Configuration screens
   */
  public function testConfig() {
    if ($this->privileged_user) $this->drupalLogin($this->privileged_user);
    // Navigation to configuration form
    $this->drupalGet('admin/config');
    $this->assertLink('Report Configuration');

    // Verify general configuration form
    $this->clickLink('Report Configuration');
    $this->assertField('default_skin', 'Default Skin');
    $this->assertField('input_format', 'Input Format');

    // Verify list of Data Sources
    $this->drupalGet('admin/config/content/forena/data');
    $this->assertText('forena_help', 'Help data source exists');
    $this->assertText('drupal', 'Drupal data source exists');
    $this->assertLink('edit', 0, 'Edit link is available.');

    // Veriify Data Source save
    $this->drupalGet('admin/config/content/forena/data/drupal');
    $this->assertField('debug', 'Debug field Exists');
    $edit['debug'] = '1';
    $this->drupalPostForm(NULL, $edit,  t('Save'));
    $this->assertFieldChecked('edit-debug');

    // @TODO: Email Configuration

  }

  /**
   * Test Forena Report.
   */
  public function testReport() {
    // Simple Report.
    if ($this->privileged_user) $this->drupalLogin($this->privileged_user);
    $this->drupalGet('reports/sample.states');
    $this->assertText('Simple Table', 'The report title is there.');
    $this->assertText('Alaska', 'A state in the report exists');
    
    // Test ajax callback
    $this->drupalGet('reports/sample.states/nojs/sample-report/html');
    $this->assertText('Simple Table', 'The report title is there.'); 

    // Report with links
    $this->drupalGet('reports/sample.state_summary');
    $this->assertText('FL - Florida', 'A state in the report exists');
  }

  /**
   * Test hooks Implementations
   */
  public function testHooks() {
    $repository = $this->frx->dataManager()->repository('test');
    $this->assertTrue($repository !== NULL, 'Test Data Repository Defined');
    $title = $repository->conf['title'];
    $this->assertEqual($title, 'Altered Test Data');
    $plugins = AppService::instance()->getRendererPlugins();
    $this->assertTrue(isset($plugins['FrxCrosstab']), "Crosstab Renderer Exists");
  }

  /**
   * Test Document Types
   */
  public function testDocumentTypes() {
    $doc_types = $this->frx->documentManager()->getDocTypes();
    $this->assertTrue(count($doc_types)>0, "Found Document types");
    $this->assertTrue(array_search('csv', $doc_types)!==FALSE, "CSV Exists");
  }

  /**
   * Test Ajax command list
   */
  public function testAjaxCommands() {
    $plugins = AppService::instance()->getAjaxPlugins();
    $this->assertTrue(!empty($plugins['add_css']), 'add_css');
    $this->assertTrue(!empty($plugins['after']), 'after');
    $this->assertTrue(!empty($plugins['alert']), 'alert');
    $this->assertTrue(!empty($plugins['append']), 'append');
    $this->assertTrue(!empty($plugins['before']), 'before');
    $this->assertTrue(!empty($plugins['changed']), 'changed');
    $this->assertTrue(!empty($plugins['closeDialog']), 'closeDialog');
    $this->assertTrue(!empty($plugins['closeModalDialog']), 'closeModalDialog');
    $this->assertTrue(!empty($plugins['css']), 'css');
    $this->assertTrue(!empty($plugins['data']), 'data');
    $this->assertTrue(!empty($plugins['html']), 'html');
    $this->assertTrue(!empty($plugins['invoke']), 'invoke');
    $this->assertTrue(!empty($plugins['openDialog']), 'openDialog');
    $this->assertTrue(!empty($plugins['openModalDialog']), 'openModalDialog');
    $this->assertTrue(!empty($plugins['prepend']), 'prepend');
    $this->assertTrue(!empty($plugins['replace']), 'replace');
    $this->assertTrue(!empty($plugins['restripe']), 'restripe');
    $this->assertTrue(!empty($plugins['settings']), 'settings');
  }

  /**
   * Test Crosstab renderer
   */
  public function testCrossTab() {
    if ($this->privileged_user) $this->drupalLogin($this->privileged_user);
    $this->drupalGet('reports/crosstab');
    $this->assertText('Male');
    $this->assertText('Female');
    $this->assertText('Unknown');
  }

}