<?php

namespace Drupal\Tests\migrate_override\Kernel;

use Drupal\migrate_override\OverrideManagerService;
use Drupal\migrate_override\OverrideManagerServiceInterface;
use Drupal\node\Entity\Node;

/**
 * Tests override Functionality.
 *
 * @group migrate_override
 */
class OverrideTest extends MigrateOverrideTestBase {

  public static $modules = ['override_test'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->createContentType();
    $this->addTextField('field_test_override');
    $this->addTextField('field_test_nooverride');
    $this->addTextField('field_test_missing');

    $this->config->set('entities.node.test_type.fields.field_test_override', OverrideManagerServiceInterface::FIELD_OVERRIDEABLE);
    $this->config->set('entities.node.test_type.fields.title', OverrideManagerServiceInterface::FIELD_OVERRIDEABLE);
    $this->config->set('entities.node.test_type.fields.field_test_missing', OverrideManagerServiceInterface::FIELD_OVERRIDEABLE);
    $this->config->set('entities.node.test_type.migrate_override_enabled', TRUE);
    $this->config->save();
    $this->overrideManager->createBundleField('node', 'test_type');
  }

  /**
   * Tests migrations with no override.
   */
  public function testNoOverride() {
    /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
    $migration = $this->migrationPluginManager->createInstance('override_test_migration');
    $this->executeMigration($migration);
    $nodes = Node::loadMultiple();
    $this->assertCount(2, $nodes);
    $this->assertSame('test_title_1', $nodes[1]->label());
    $this->assertSame('test_title_2', $nodes[2]->label());
    $this->assertSame('test_override', $nodes[1]->field_test_override->value);
    $this->assertSame('nooverride', $nodes[1]->field_test_nooverride->value);

    $nodes[1]->field_test_override = "Custom Value";
    $nodes[1]->setTitle('Custom Title');
    $nodes[1]->field_test_nooverride = "Custom Value";

    $nodes[1]->save();

    $nodes = Node::loadMultiple();
    $this->assertCount(2, $nodes);
    $this->assertSame('Custom Title', $nodes[1]->label());
    $this->assertSame('Custom Value', $nodes[1]->field_test_override->value);
    $this->assertSame('Custom Value', $nodes[1]->field_test_nooverride->value);

    $migration->getIdMap()->prepareUpdate();
    $this->executeMigration($migration);

    $nodes = Node::loadMultiple();
    $this->assertCount(2, $nodes);
    $this->assertSame('test_title_1', $nodes[1]->label());
    $this->assertSame('test_title_2', $nodes[2]->label());
    $this->assertSame('test_override', $nodes[1]->field_test_override->value);
    $this->assertSame('nooverride', $nodes[1]->field_test_nooverride->value);
  }

  /**
   * Tests Migration with an override.
   */
  public function testOverride() {
    $migration = $this->migrationPluginManager->createInstance('override_test_migration');
    $this->executeMigration($migration);
    $nodes = Node::loadMultiple();
    $this->assertCount(2, $nodes);
    $this->assertSame('test_title_1', $nodes[1]->label());
    $this->assertSame('test_title_2', $nodes[2]->label());
    $this->assertSame('test_override', $nodes[1]->field_test_override->value);
    $this->assertSame('nooverride', $nodes[1]->field_test_nooverride->value);

    $nodes[1]->field_test_override = "Custom Value";
    $nodes[1]->setTitle('Custom Title');
    $nodes[1]->field_test_nooverride = "Custom Value";
    $override = [
      'title' => OverrideManagerServiceInterface::ENTITY_FIELD_OVERRIDDEN,
      'field_test_override' => OverrideManagerServiceInterface::ENTITY_FIELD_OVERRIDDEN,
    ];
    $nodes[1]->migrate_override_data = [['value' => serialize($override)]];
    $nodes[1]->save();

    $nodes = Node::loadMultiple();
    $this->assertCount(2, $nodes);
    $this->assertSame('Custom Title', $nodes[1]->label());
    $this->assertSame('Custom Value', $nodes[1]->field_test_override->value);
    $this->assertSame('Custom Value', $nodes[1]->field_test_nooverride->value);
    $this->assertSame(serialize($override), $nodes[1]->migrate_override_data->value);

    $migration->getIdMap()->prepareUpdate();
    $this->executeMigration($migration);

    $nodes = Node::loadMultiple();
    $this->assertCount(2, $nodes);
    $this->assertSame('Custom Title', $nodes[1]->label());
    $this->assertSame('test_title_2', $nodes[2]->label());
    $this->assertSame('Custom Value', $nodes[1]->field_test_override->value);
    $this->assertSame('nooverride', $nodes[1]->field_test_nooverride->value);

  }

  /**
   * Tests Migration with a missing source.
   */
  public function testMissingSource() {
    /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
    $migration = $this->migrationPluginManager->createInstance('missing_source_test_migration');
    $this->executeMigration($migration);
    $nodes = Node::loadMultiple();
    $this->assertCount(2, $nodes);
    $this->assertSame('test_title_1', $nodes[1]->label());
    $this->assertSame('test_title_2', $nodes[2]->label());
    // Make a change to test against later.
    $nodes[1]->field_test_missing = "123";
    $this->overrideManager->setEntityFieldStatus($nodes[1], 'field_test_missing', OverrideManagerService::ENTITY_FIELD_OVERRIDDEN);
    $nodes[1]->save();
    // Verify save.
    $nodes = Node::loadMultiple();
    $this->assertCount(2, $nodes);
    $this->assertSame('123', $nodes[1]->field_test_missing->value);
    // Re-run migration.
    $migration->getIdMap()->prepareUpdate();
    $this->executeMigration($migration);
    // Verify value did not reset to NULL.
    $nodes = Node::loadMultiple();
    $this->assertCount(2, $nodes);
    $this->assertSame('123', $nodes[1]->field_test_missing->value);
  }

}
