<?php

namespace Drupal\Tests\owms\Functional;

use function Composer\Autoload\includeFile;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\owms\Entity\OwmsData;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;

/**
 * Tests the OWMS UI and other OWMS workflows.
 *
 * @group owms
 */
class OwmsBrowserTest extends BrowserTestBase {

  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['owms', 'node', 'system', 'field'];

  /**
   * @var OwmsData
   */
  protected $owmsData;

  /**
   * @var \Drupal\owms\OwmsManager
   */
  protected $owmsManager;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $admin_user = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($admin_user);

    $this->createContentType(['type' => 'article']);

    // The ID of the OwmsData entity is based on the endpoint, but converted
    // to lowercase.
    $this->owmsData = OwmsData::create([
      'endpoint' => 'TestConfig',
    ]);
    $this->owmsData->save();
    $this->owmsManager = \Drupal::getContainer()->get('owms.manager');

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'owms_field',
      'entity_type' => 'node',
      'type' => 'owms_list_item',
      'settings' => ['owms_config' => 'testconfig'],
    ]);
    $field_storage->save();

    $instance = FieldConfig::create([
      'field_name' => 'owms_field',
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => $this->randomMachineName(),
    ]);
    $instance->save();

  }

  /**
   * Test existence of the configuration page.
   */
  public function testConfigurationPage() {
    $this->drupalGet('admin/config/owms');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests adding a configuration entity.
   */
  public function testAddOwmsEntity() {
    $this->drupalGet('/admin/config/owms/add');
    // Check if the add entity page works with the right permission.
    $this->assertSession()->statusCodeEquals(200);

    // Don't allow returning a non valid endpoint.
    $this->setExpectedException(\InvalidArgumentException::class, 'Input "endpoint" cannot take "some_random_url" as a value');

    // Fill in the form with an invalid value.
    $this->drupalPostForm(NULL, [
      'endpoint' => 'some_random_url',
    ], 'Save');
  }

  /**
   * Tests the deprecation workflow.
   *
   * First add OWMS data to the OWMS config object. Then assign the value
   * that will become deprecated.
   *
   * For some reason the status page returns a 500 error.
   * @todo Make this test work.
   */
  public function testDeprecatedStatusPage() {

    $this->owmsData->set('items', [
      [
        'identifier' => 'foo',
        'label' => 'foo',
        'deprecated' => TRUE,
      ],
    ])->save();

    $node = Node::create([
      'type' => 'article',
      'title' => 'bar',
    ]);
    $node->set('owms_field', 'foo');
    $node->save();

    $this->drupalGet('admin/reports/status');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('The following entities hold fields with deprecated OWMS items.');
    $this->assertSession()->pageTextContains('bar');

    $this->owmsData->set('items', [
      [
        'identifier' => 'foo',
        'label' => 'foo',
        'deprecated' => FALSE,
      ],
    ])->save();

    $this->drupalGet('admin/reports/status');
    $this->assertSession()->pageTextNotContains('The following entities hold fields with deprecated OWMS items.');
    $this->assertSession()->pageTextNotContains('bar');

  }

}
