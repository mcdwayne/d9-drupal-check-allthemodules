<?php

namespace Drupal\Tests\acquia_contenthub\Kernel;

use Drupal\Tests\acquia_contenthub\Kernel\Core\FileSystemTrait;

/**
 * Class MediaImportExportTest.
 *
 * @group acquia_contenthub
 *
 * @package Drupal\Tests\acquia_contenthub\Kernel
 */
class MediaImportExportTest extends ImportExportTestBase {

  use FileSystemTrait;

  /**
   * {@inheritdoc}
   */
  protected $fixtures = [
    [
      'cdf' => 'media/node-with-media-image.json',
      'expectations' => 'expectations/media/reference_1.php',
    ],
    [
      'cdf' => 'media/node-with-multiple-media.json',
      'expectations' => 'expectations/media/reference_2.php',
    ],
    [
      'cdf' => 'media/node-with-media-image.json',
      'expectations' => 'expectations/media/reference_3.php',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'user',
    'node',
    'file',
    'field',
    'image',
    'media_library',
    'media',
    'action',
    'views',
    'depcalc',
    'acquia_contenthub',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installSchema('user', ['users_data']);
    $this->installEntitySchema('node');
    $this->installEntitySchema('view');
    $this->installEntitySchema('media');
    $this->fileSystemSetUp();
  }

  /**
   * Tests import/export of node with media.
   *
   * @param int $delta
   *   Delta.
   * @param array $validate_data
   *   Data.
   * @param string $export_type
   *   Entity type ID.
   * @param string $export_uuid
   *   Uuid.
   *
   * @throws \Exception
   *
   * @dataProvider mediaImportExportDataProvider
   */
  public function testMediaImportExport($delta, array $validate_data, $export_type, $export_uuid) {
    parent::contentEntityImportExport($delta, $validate_data, $export_type, $export_uuid);
  }

  /**
   * Data provider for testFileImportExport.
   */
  public function mediaImportExportDataProvider() {
    yield [
      0,
      [
        [
          'type' => 'file',
          'uuid' => '083607fb-df43-4efb-a66c-7a44fe018a62',
        ],
      ],
      'node',
      '91c820a4-d696-4e99-8fdb-6314e66ddee6',
    ];
    yield [
      1,
      [
        [
          'type' => 'file',
          'uuid' => '3cd48a71-8215-4a46-806a-61fdb5cc05d5',
        ],
        [
          'type' => 'file',
          'uuid' => 'fcb8efaa-9431-4750-9703-b783b22a4a9f',
        ],
      ],
      'node',
      'd504aa35-99da-4f9a-b3d7-ac11ace97cf8',
    ];
    yield [
      2,
      [
        [
          'type' => 'media',
          'uuid' => '0f353016-de0f-4268-859c-9ed58a4d6f36',
        ],
      ],
      'node',
      '91c820a4-d696-4e99-8fdb-6314e66ddee6',
    ];
  }

}
