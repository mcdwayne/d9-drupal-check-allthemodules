<?php

namespace Drupal\Tests\acquia_contenthub\Kernel;

/**
 * Class MenuEntityTest.
 *
 * @group acquia_contenthub
 *
 * @package Drupal\Tests\acquia_contenthub\Kernel
 */
class MenuTest extends ImportExportTestBase {

  protected $fixtures = [
    [
      'cdf' => 'menu/menu_external.json',
      'expectations' => 'expectations/menu/menu_external.php',
    ],
    [
      'cdf' => 'menu/menu_entity.json',
      'expectations' => 'expectations/menu/menu_entity.php',
    ],
    [
      'cdf' => 'menu/menu_internal.json',
      'expectations' => 'expectations/menu/menu_internal.php',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'user',
    'node',
    'menu_link_content',
    'link',
    'menu_ui',
    'field',
    'depcalc',
    'acquia_contenthub',
  ];

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function setUp() {
    parent::setUp();

    $this->installEntitySchema('menu_link_content');
    $this->installEntitySchema('node');
    $this->installSchema('user', ['users_data']);
  }

  /**
   * @dataProvider menuImportExportDataProvider
   */
  public function testMenuImportExport($delta, $validate_data, $export_type, $export_uuid) {
    parent::contentEntityImportExport($delta, $validate_data, $export_type, $export_uuid);
  }

  /**
   * Data provider for menuImportExportDataProvider.
   *
   * @return array
   *   Array of import and export data.
   */
  public function menuImportExportDataProvider() {
    return [
      [0, [['type' => 'menu_link_content', 'uuid' => 'b1dd007c-6720-4497-b54c-879ea2eb6898']], 'menu_link_content', 'b1dd007c-6720-4497-b54c-879ea2eb6898'],
      [1, [['type' => 'menu_link_content', 'uuid' => 'b335c1e4-1ee7-42b0-9e51-ec24482ca08a']], 'menu_link_content', 'b335c1e4-1ee7-42b0-9e51-ec24482ca08a'],
      [2, [['type' => 'menu_link_content', 'uuid' => 'd675d768-a283-41f7-b136-a50603e5b76a']], 'menu_link_content', 'd675d768-a283-41f7-b136-a50603e5b76a'],
    ];
  }

}
