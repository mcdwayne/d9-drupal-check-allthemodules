<?php

namespace Drupal\Tests\acquia_contenthub\Kernel;

/**
 * Class BlockTest.
 *
 * @group acquia_contenthub
 *
 * @package Drupal\Tests\acquia_contenthub\Kernel
 */
class BlockTest extends ImportExportTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'user',
    'node',
    'field',
    'block_content',
  ];

  /**
   * Fixture files.
   *
   * @var array
   */
  protected $fixtures = [
    0 => [
      'cdf' => 'block/block1.json',
      'expectations' => 'expectations/block/block_content1.php',
    ],
    1 => [
      'cdf' => 'block/block2.json',
      'expectations' => 'expectations/block/block_content2.php',
    ],
    2 => [
      'cdf' => 'block/block_translations.json',
      'expectations' => 'expectations/block/block_translations.php',
    ],
  ];

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('block_content');
    $this->installSchema('user', ['users_data']);
  }

  /**
   * Tests "block_content" Drupal entity.
   *
   * @param array $args
   *   Arguments. @see ImportExportTestBase::contentEntityImportExport() for the
   *   details.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @dataProvider blockDataProvider
   */
  public function testBlock(...$args) {
    parent::contentEntityImportExport(...$args);
  }

  /**
   * Data provider for testBlock.
   *
   * @return array
   *   Data provider set.
   */
  public function blockDataProvider() {
    return [
      // Standard text block.
      [
        0,
        [
          [
            'type' => 'block_content',
            'uuid' => '6bf9ea86-92ea-498e-bf5f-4c137a767af3',
          ],
        ],
        'block_content',
        '6bf9ea86-92ea-498e-bf5f-4c137a767af3',
      ],
      // Block entity with a custom field.
      [
        1,
        [
          [
            'type' => 'block_content',
            'uuid' => '94b4093e-fb02-4d53-8ecc-031f85fd1db2',
          ],
        ],
        'block_content',
        '94b4093e-fb02-4d53-8ecc-031f85fd1db2',
      ],
      // Multilingual Block.
      [
        2,
        [
          [
            'type' => 'block_content',
            'uuid' => '0e74a49f-eb49-43ef-9d7a-50c6f500ec87',
          ],
        ],
        'block_content',
        '0e74a49f-eb49-43ef-9d7a-50c6f500ec87',
      ],
    ];
  }

}
