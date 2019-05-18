<?php

namespace Drupal\Tests\monster_menus\Kernel\migrate;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\migrate_drupal\Kernel\MigrateDrupalTestBase;

/**
 * Base class for the paragraph migrations tests.
 */
abstract class MonsterMenusMigrationTestBase extends MigrateDrupalTestBase
{

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();
        $this->loadFixture(__DIR__ . '/../../../fixtures/migrate/drupal7.php');

    }

    /**
     * Check if a field storage config entity was created for the node.
     *
     * @param string $field_name
     *   The field to test for.
     * @param string $field_type
     *   The expected field type.
     */
    protected function assertNodeFieldExists($field_name, $field_type)
    {
        $field_storage = FieldStorageConfig::loadByName('node', $field_name);
        $this->assertNotNull($field_storage);
        $this->assertEquals($field_type, $field_storage->getType());
    }

    /**
     * Check if a field storage config entity was created for the paragraph.
     *
     * @param string $field_name
     *   The field to test for.
     * @param string $field_type
     *   The expected field type.
     */
    protected function assertParagraphFieldExists($field_name, $field_type)
    {
        $field_storage = FieldStorageConfig::loadByName('paragraph', $field_name);
        $this->assertNotNull($field_storage);
        $this->assertEquals($field_type, $field_storage->getType());
    }


}
