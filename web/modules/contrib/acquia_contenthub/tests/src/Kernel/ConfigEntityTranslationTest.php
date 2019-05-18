<?php

namespace Drupal\Tests\acquia_contenthub\Kernel;

use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Class ConfigEntityTranslationTest.
 *
 * @group acquia_contenthub
 *
 * @package Drupal\Tests\acquia_contenthub\Kernel
 */
class ConfigEntityTranslationTest extends ImportExportTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'block_content',
    'language',
    'locale',
    'config_translation',
    'views',
    'node',
    'user',
    'acquia_contenthub_publisher',
  ];

  /**
   * Fixture files.
   *
   * @var array
   */
  protected $fixtures = [
    1 => [
      'cdf' => 'node/node_type_config_translations.json',
      'expectations' => 'expectations/node/node_type_config_translations.php',
    ],
    2 => [
      'cdf' => 'block/block_config_translations.json',
      'expectations' => 'expectations/block/block_config_translations.php',
    ],
    3 => [
      'cdf' => 'taxonomy_vocabulary/taxonomy_vocabulary_config_translations.json',
      'expectations' => 'expectations/taxonomy_vocabulary/taxonomy_vocabulary_config_translations.php',
    ],
    4 => [
      'cdf' => 'menu/menu_config_translations.json',
      'expectations' => 'expectations/menu/menu_config_translations.php',
    ],
    5 => [
      'cdf' => 'user/user_role_config_translations.json',
      'expectations' => 'expectations/user/user_role_config_translations.php',
    ],
    6 => [
      'cdf' => 'view/view_config_translations.json',
      'expectations' => 'expectations/view/view_config_translations.php',
    ],
  ];

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('acquia_contenthub_publisher', ['acquia_contenthub_publisher_export_tracking']);
    $this->installEntitySchema('block_content');
    $this->installConfig(['language', 'locale']);
    $this->installSchema('locale', [
      'locales_source',
      'locales_target',
      'locales_location',
    ]);
    $this->installEntitySchema('view');

    // Setup additional languages.
    foreach (['be', 'ru'] as $langcode) {
      $language = ConfigurableLanguage::create([
        'id' => $langcode,
        'label' => $langcode,
      ]);
      $language->save();
    }

    // Install "Seven" theme for the block configuration test ($fixtures[2]).
    \Drupal::service('theme_handler')->install(['seven']);
    \Drupal::theme()->setActiveTheme(\Drupal::service('theme.initialization')->initTheme('seven'));
  }

  /**
   * Tests Configuration Entity translations import/export.
   *
   * @param array $args
   *   Arguments. @see ImportExportTestBase::contentEntityImportExport() for the
   *   details.
   *
   * @throws \Exception
   *
   * @dataProvider configEntityTranslationsDataProvider
   */
  public function testConfigEntityTranslations(...$args) {
    parent::configEntityImportExport(...$args);
  }

  /**
   * Data provider for testConfigEntityTranslations.
   *
   * @return array
   *   Data provider set.
   */
  public function configEntityTranslationsDataProvider() {
    return [
      [
        1,
        [
          [
            'type' => 'node_type',
            'uuid' => '06bddad6-c004-414f-802a-eade9b2624b6',
          ],
        ],
        'node_type',
        '06bddad6-c004-414f-802a-eade9b2624b6',
      ],
      [
        2,
        [
          [
            'type' => 'block',
            'uuid' => '5067cba4-44ba-4e70-ba99-5626343c6b41',
          ],
        ],
        'block',
        '5067cba4-44ba-4e70-ba99-5626343c6b41',
      ],
      [
        3,
        [
          [
            'type' => 'taxonomy_vocabulary',
            'uuid' => 'b6249a32-8c37-4d24-a0f9-c8a4d40a410a',
          ],
        ],
        'taxonomy_vocabulary',
        'b6249a32-8c37-4d24-a0f9-c8a4d40a410a',
      ],
      [
        4,
        [
          [
            'type' => 'menu',
            'uuid' => '33e106d4-b365-4bb1-b44f-8beeecb4616f',
          ],
        ],
        'menu',
        '33e106d4-b365-4bb1-b44f-8beeecb4616f',
      ],
      [
        5,
        [
          [
            'type' => 'user_role',
            'uuid' => 'b7a60b03-3ae2-4480-b261-f72021817346',
          ],
        ],
        'user_role',
        'b7a60b03-3ae2-4480-b261-f72021817346',
      ],
      [
        6,
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
