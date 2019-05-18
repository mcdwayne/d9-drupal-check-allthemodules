<?php

namespace Drupal\Tests\acquia_contenthub\Kernel;

/**
 * Class TermImportExportTest.
 *
 * @group acquia_contenthub
 *
 * @package Drupal\Tests\acquia_contenthub\Kernel
 */
class TermImportExportTest extends ImportExportTestBase {

  protected $fixtures = [
    [
      'cdf' => 'node/node_term_page.json',
      'expectations' => 'expectations/node/node_term_page.php',
    ],
    [
      'cdf' => 'taxonomy_term/translated-terms.json',
      'expectations' => 'expectations/taxonomy_term/translated_terms.php',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'taxonomy',
    'user',
    'node',
    'field',
    'depcalc',
    'acquia_contenthub',
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
    $this->installEntitySchema('taxonomy_term');
  }

  /**
   * @dataProvider termImportExportDataProvider
   */
  public function testTermImportExport(...$args) {
    parent::contentEntityImportExport(...$args);
  }

  /**
   * Data provider for testTermImportExport.
   *
   * @return array
   *   Test data sets.
   */
  public function termImportExportDataProvider() {
    return [
      [
        0,
        [
          ['type' => 'node', 'uuid' => '1264093e-bdad-41a7-a059-1904a5e6d8d6'],
          [
            'type' => 'taxonomy_term',
            'uuid' => '20b902fa-e233-4cfc-9012-6824a1d256ea',
          ],
          [
            'type' => 'taxonomy_term',
            'uuid' => 'e07f1e2a-83ec-44ba-b874-9cbb00140675',
          ],
          [
            'type' => 'taxonomy_term',
            'uuid' => '17ce8cc4-edfe-4ca7-809d-93abaf09960c',
          ],
        ],
        'node',
        '1264093e-bdad-41a7-a059-1904a5e6d8d6',
      ],
      [
        1,
        [
          [
            'type' => 'taxonomy_term',
            'uuid' => 'ccd971d2-d5fa-41af-b9ce-fdee956f3c08',
          ],
        ],
        'taxonomy_term',
        'ccd971d2-d5fa-41af-b9ce-fdee956f3c08',
      ],
    ];
  }

}
