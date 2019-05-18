<?php

namespace Drupal\Tests\acquia_contenthub\Kernel;

/**
 * Class ViewConfigTest.
 *
 * This is a generic test of import/export of a complex Configuration Entity.
 *
 * @group acquia_contenthub
 *
 * @package Drupal\Tests\acquia_contenthub\Kernel
 */
class ViewConfigTest extends ImportExportTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'views',
  ];

  /**
   * Fixture files.
   *
   * @var array
   */
  protected $fixtures = [
    1 => [
      'cdf' => 'view/view_config.json',
      'expectations' => 'expectations/view/view_config.php',
    ],
  ];

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('view');
  }

  /**
   * Tests View Configuration Entity import/export.
   *
   * @param array $args
   *   Arguments. @see ImportExportTestBase::contentEntityImportExport() for the
   *   details.
   *
   * @throws \Exception
   *
   * @dataProvider viewConfigEntityDataProvider
   */
  public function testViewConfigEntity(...$args) {
    parent::configEntityImportExport(...$args);
  }

  /**
   * Data provider for testViewConfigEntity.
   *
   * @return array
   *   Data provider set.
   */
  public function viewConfigEntityDataProvider() {
    return [
      [
        1,
        [
          [
            'type' => 'view',
            'uuid' => '0204f032-73dd-4d0f-83df-019631d86563',
          ],
        ],
        'view',
        '0204f032-73dd-4d0f-83df-019631d86563',
      ],
    ];
  }

}
