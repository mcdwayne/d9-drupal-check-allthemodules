<?php

namespace Drupal\Tests\acquia_contenthub\Kernel;

/**
 * Class ParagraphsImportExportTest.
 *
 * @group acquia_contenthub
 *
 * @requires module paragraphs
 *
 * @package Drupal\Tests\acquia_contenthub\Kernel
 */
class ParagraphsImportExportTest extends ImportExportTestBase {

  /**
   * {@inheritdoc}
   */
  protected $fixtures = [
    [
      'cdf' => 'paragraphs/node-with-paragraph.json',
      'expectations' => 'expectations/paragraphs/node_with_paragraph.php',
    ],
    [
      'cdf' => 'paragraphs/node-with-multi-level-paragraphs.json',
      'expectations' => 'expectations/paragraphs/node_with_multi_level_paragraphs.php',
    ],
    [
      'cdf' => 'paragraphs/translated-paragraphs.json',
      'expectations' => 'expectations/paragraphs/translated_paragraphs.php',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'user',
    'node',
    'field',
    'paragraphs',
    'file',
  ];

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installSchema('user', ['users_data']);
    $this->installEntitySchema('node');

    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);

    $this->installEntitySchema('paragraph');
    \Drupal::moduleHandler()->loadInclude('paragraphs', 'install');
  }

  /**
   * Tests paragraphs.
   *
   * @param mixed $args
   *   Data.
   *
   * @dataProvider paragraphsDataProvider
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testParagraphs(...$args) {
    parent::contentEntityImportExport(...$args);
  }

  /**
   * Data provider for testParagraphs.
   */
  public function paragraphsDataProvider() {
    yield [
      0,
      [
        [
          'type' => 'node',
          'uuid' => '81735e3e-46cf-4c7a-b129-6e5e3b27c66b',
        ],
        [
          'type' => 'paragraph',
          'uuid' => '54d27dc3-f079-483f-8919-ed579b455271',
        ],
      ],
      'node',
      '81735e3e-46cf-4c7a-b129-6e5e3b27c66b',
    ];
    yield [
      1,
      [
        [
          'type' => 'node',
          'uuid' => '81735e3e-46cf-4c7a-b129-6e5e3b27c66b',
        ],
        [
          'type' => 'paragraph',
          'uuid' => '72ad889a-f900-4b2f-ba91-a22fc28e0719',
        ],
        [
          'type' => 'paragraph',
          'uuid' => 'b7ff50f4-e371-4360-8bc4-7020362de52b',
        ],
      ],
      'node',
      '81735e3e-46cf-4c7a-b129-6e5e3b27c66b',
    ];
    yield [
      2,
      [
        [
          'type' => 'node',
          'uuid' => '50b7a410-35d9-4575-8548-256e958d57de',
        ],
        [
          'type' => 'paragraph',
          'uuid' => '26a2f959-b982-41bc-a497-764709dfbeeb',
        ],
        [
          'type' => 'paragraph',
          'uuid' => 'cd79bce5-4d18-4cc2-a202-3c08cea7701d',
        ],
      ],
      'node',
      '50b7a410-35d9-4575-8548-256e958d57de',
    ];
  }

}
