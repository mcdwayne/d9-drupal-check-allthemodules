<?php

namespace Drupal\Tests\migrate_override\Kernel;

use Drupal\Core\Config\Schema\SchemaIncompleteException;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests update hooks for migrate_override.
 *
 * @group migrate_override
 */
class UpdateTest extends KernelTestBase {
  public static $modules = ['system', 'migrate_override', 'node'];

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->configFactory = $this->container->get('config.factory');
    $this->installConfig('migrate_override');
  }

  /**
   * Tests config schema update.
   */
  public function testUpdate8001() {
    $data = [
      'node' => [
        'page' => [
          'migrate_override_enabled' => FALSE,
        ],
      ],
    ];
    $config = $this->configFactory->getEditable('migrate_override.migrateoverridesettings');
    $config->setData($data);
    try {
      $config->save(TRUE);
    }
    catch (SchemaIncompleteException $e) {
    }

    $config = $this->configFactory->get('migrate_override.migrateoverridesettings');
    $this->assertSame(['page' => ['migrate_override_enabled' => FALSE]], $config->get('node'));

    module_load_include('install', 'migrate_override');
    migrate_override_update_8001();

    $config = $this->configFactory->get('migrate_override.migrateoverridesettings');

    $this->assertNull($config->get('node'));
    $this->assertSame(['page' => ['migrate_override_enabled' => FALSE]], $config->get('entities.node'));
  }

}
