<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Plugin\migrate\source\uc6;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests D6 Ubercart view mode source plugin.
 *
 * @covers \Drupal\commerce_migrate_ubercart\Plugin\migrate\source\uc6\ViewMode
 * @group commerce_migrate
 * @group commerce_migrate_ubercart_uc6
 */
class ViewModeTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_migrate_ubercart',
    'migrate_drupal',
    'node',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['source_data']['content_node_field_instance'] = [
      [
        'type_name' => 'node',
        'display_settings' => serialize([
          'weight' => '31',
          'parent' => '',
          'label' => [
            'format' => 'above',
          ],
          'teaser' => [
            'format' => 'default',
            'exclude' => 0,
          ],
          'full' => [
            'format' => 'default',
            'exclude' => 0,
          ],
          4 => [
            'format' => 'default',
            'exclude' => 0,
          ],
        ]),
      ],
      [
        'type_name' => 'product',
        'display_settings' => serialize([
          'weight' => '31',
          'parent' => '',
          'label' => [
            'format' => 'above',
          ],
          'teaser' => [
            'format' => 'default',
            'exclude' => 0,
          ],
          'full' => [
            'format' => 'default',
            'exclude' => 0,
          ],
          4 => [
            'format' => 'default',
            'exclude' => 0,
          ],
        ]),
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = [
      [
        'entity_type' => 'node',
        'view_mode' => 4,
        'type_name' => 'node',
      ],
      [
        'entity_type' => 'node',
        'view_mode' => 'teaser',
        'type_name' => 'node',
      ],
      [
        'entity_type' => 'node',
        'view_mode' => 'full',
        'type_name' => 'node',
      ],

    ];

    return $tests;
  }

}
