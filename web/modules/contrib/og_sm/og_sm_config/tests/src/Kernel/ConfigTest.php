<?php

namespace Drupal\Tests\og_sm_config\Kernel;

use Drupal\og_sm\OgSm;
use Drupal\Tests\og_sm\Kernel\OgSmKernelTestBase;

/**
 * Tests Site Configuration API.
 *
 * @group og_sm
 */
class ConfigTest extends OgSmKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'og_sm_config',
    'og_sm_config_test',
  ];

  /**
   * The configuration override object.
   *
   * @var \Drupal\og_sm_config\Config\SiteConfigFactoryOverrideInterface
   */
  protected $configFactoryOverride;

  /**
   * Site node 1.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $site1;

  /**
   * Site node 2.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $site2;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->configFactoryOverride = $this->container->get('og_sm.config_factory_override');

    $type = $this->createGroupNodeType(OgSmKernelTestBase::TYPE_IS_GROUP);
    OgSm::setSiteType($type, TRUE);
    $type->save();
    $this->site1 = $this->createGroup($type->id());
    $this->site2 = $this->createGroup($type->id());
  }

  /**
   * Test DB operations.
   */
  public function testDbCrud() {
    $site1_config = $this->configFactoryOverride->getOverride($this->site1, 'og_sm_config_test.settings');
    $site2_config = $this->configFactoryOverride->getOverride($this->site2, 'og_sm_config_test.settings');

    // By default no variables.
    $this->assertEquals([], $site1_config->get());

    // Store some variables.
    $var11 = TRUE;
    $var12 = NULL;
    $var13 = 123;
    $var14 = '123';
    $var15 = [];
    $var16 = [123, 'test123', 'key' => 'value'];
    $var21 = FALSE;

    $site2_config->set('test_1', $var21)->save();
    $site1_config->set('test_3', $var13)->save();
    $site1_config->set('test_1', $var11)->save();
    $site1_config->set('test_5', $var15)->save();
    $site1_config->set('test_2', $var12)->save();
    $site1_config->set('test_6', $var16)->save();
    $site1_config->set('test_4', $var14)->save();

    // Update variable.
    $var14 = '321';
    $site1_config->set('test_4', $var14)->save();

    // Get all variables (expected ordered by name).
    $expected = [
      'test_1' => $var11,
      'test_2' => $var12,
      'test_3' => $var13,
      'test_4' => $var14,
      'test_5' => $var15,
      'test_6' => $var16,
    ];
    $this->assertEquals($expected, $site1_config->get());

    // Delete a variable.
    $site1_config->clear('test_4');
    unset($expected['test_4']);
    $this->assertEquals($expected, $site1_config->get());

    // Delete all variables.
    $site1_config->delete();
    $this->assertEquals([], $site1_config->get());
  }

  /**
   * Test the variable functions.
   */
  public function testOperations() {
    $site1_config = $this->configFactoryOverride->getOverride($this->site1, 'og_sm_config_test.settings');
    $site2_config = $this->configFactoryOverride->getOverride($this->site2, 'og_sm_config_test.settings');

    // Store some values in the DB.
    $var11 = TRUE;
    $var13 = 2;
    $var15 = [1 => 'test1', 'two' => 'test two'];
    $var21 = FALSE;
    $site1_config->set('test_1', $var11)->save();
    $site1_config->set('test_3', $var13)->save();
    $site1_config->set('test_5', $var15)->save();
    $site2_config->set('test_1', $var21)->save();

    // Get all the variables for a Site.
    $expected = ['test_1' => $var11, 'test_3' => $var13, 'test_5' => $var15];
    $variables = $site1_config->get();
    $this->assertEquals($expected, $variables);

    // Get non existing variable.
    $this->assertNull($site1_config->get('non_existing'));

    // Get single existing variable.
    $this->assertEquals($var13, $site1_config->get('test_3'));

    // Set a variable.
    $var2 = 'test var 2';
    $site1_config->set('test_2', $var2);
    $expected = [
      'test_1' => $var11,
      'test_2' => $var2,
      'test_3' => $var13,
      'test_5' => $var15,
    ];
    $this->assertEquals($expected, $site1_config->get());

    // Delete a variable.
    $site1_config->clear('test_3');
    unset($expected['test_3']);
    $this->assertEquals($expected, $site1_config->get());

    // Delete all variables.
    $site1_config->delete();
    $this->assertEquals([], $site1_config->get());
  }

  /**
   * Check if the variables are deleted when the site is deleted.
   */
  public function testVariablesDeleteOnSiteDelete() {
    $this->installSchema('node', ['node_access']);
    $site1_config = $this->configFactoryOverride->getOverride($this->site1, 'og_sm_config_test.settings');
    $site1_config->set('test_1', TRUE)->save();
    $site1_config->set('test_2', 'foo')->save();
    $expected = ['test_1' => TRUE, 'test_2' => 'foo'];
    $this->assertEquals($expected, $site1_config->get());

    $this->site1->delete();
    $site1_config = $this->configFactoryOverride->getOverride($this->site1, 'og_sm_config_test.settings');
    $this->assertEquals([], $site1_config->get());
  }

  /**
   * Test copying variables from one Site to another.
   */
  public function testVariableCopyFromTo() {
    $site1_config = $this->configFactoryOverride->getOverride($this->site1, 'og_sm_config_test.settings');
    $site2_config = $this->configFactoryOverride->getOverride($this->site2, 'og_sm_config_test.settings');

    $site1_config->set('prefix_1_variable_1', 'value 1-1')->save();
    $site1_config->set('prefix_1_variable_2', 'value 1-2')->save();
    $site1_config->set('prefix_2_variable_1', 'value 2-1')->save();
    $expected = [
      'prefix_1_variable_1' => 'value 1-1',
      'prefix_1_variable_2' => 'value 1-2',
      'prefix_2_variable_1' => 'value 2-1',
    ];
    $this->assertEquals($expected, $site1_config->get(), 'Site 1 has 3 variables.');

    // Site to has no variables yet.
    $this->assertEquals([], $site2_config->get(), 'Site 2 has no variables.');

    // Copy Variables by their names.
    $site2_config->merge($site1_config->getMultiple(['prefix_1_variable_1', 'prefix_2_variable_1']))->save();
    $expected = [
      'prefix_1_variable_1' => 'value 1-1',
      'prefix_2_variable_1' => 'value 2-1',
    ];
    $this->assertEquals($expected, $site2_config->get(), 'Site 2 contains 2 copied variables by name.');

    // Copy variables by a pattern.
    $site2_config->delete();
    $this->assertEquals([], $site2_config->get(), 'Site 2 has no variables.');
    $site2_config->merge($site1_config->getMultipleByPattern('#_1_variable_#'))->save();
    $expected = [
      'prefix_1_variable_1' => 'value 1-1',
      'prefix_1_variable_2' => 'value 1-2',
    ];
    $this->assertEquals($expected, $site2_config->get(), 'Site 2 contains 2 copied variables by pattern.');

    // Copy variables by a prefix.
    $site2_config->delete();
    $this->assertEquals([], $site2_config->get(), 'Site 2 has no variables.');
    $site2_config->merge($site1_config->getMultipleByPrefix('prefix_2'))->save();
    $expected = ['prefix_2_variable_1' => 'value 2-1'];
    $this->assertEquals($expected, $site2_config->get(), 'Site 2 contains 1 copied variables by prefix.');
  }

}
