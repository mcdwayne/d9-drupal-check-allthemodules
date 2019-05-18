<?php

namespace Drupal\Tests\commerce_migrate_commerce\Kernel\Plugin\migrate\source\commerce1;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests D7 vocabulary source plugin.
 *
 * @covers \Drupal\commerce_migrate_commerce\Plugin\migrate\source\commerce1\Vocabulary
 * @group taxonomy
 */
class VocabularyTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_migrate_commerce',
    'migrate_drupal',
    'taxonomy',
  ];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['source_data']['field_config_instance'] = [
      [
        'id' => '2',
        'field_id' => '2',
        'field_name' => 'field_size',
        'entity_type' => 'commerce_product',
        'bundle' => 'top',
        'data' => 'a:7:{s:13:"default_value";N;s:11:"description";s:0:"";s:7:"display";a:5:{s:7:"default";a:5:{s:5:"label";s:5:"above";s:6:"module";s:8:"taxonomy";s:8:"settings";a:0:{}s:4:"type";s:28:"taxonomy_term_reference_link";s:6:"weight";i:5;}s:4:"full";a:4:{s:5:"label";s:5:"above";s:8:"settings";a:0:{}s:4:"type";s:6:"hidden";s:6:"weight";i:13;}s:15:"product_in_cart";a:4:{s:5:"label";s:5:"above";s:8:"settings";a:0:{}s:4:"type";s:6:"hidden";s:6:"weight";i:0;}s:12:"product_list";a:4:{s:5:"label";s:5:"above";s:8:"settings";a:0:{}s:4:"type";s:6:"hidden";s:6:"weight";i:8;}s:6:"teaser";a:4:{s:5:"label";s:5:"above";s:8:"settings";a:0:{}s:4:"type";s:6:"hidden";s:6:"weight";i:0;}}s:5:"label";s:6:"Gender";s:8:"required";i:0;s:8:"settings";a:1:{s:18:"user_register_form";b:0;}s:6:"widget";a:5:{s:6:"active";i:1;s:6:"module";s:7:"options";s:8:"settings";a:1:{s:12:"apply_chosen";i:0;}s:4:"type";s:14:"options_select";s:6:"weight";i:5;}}',
        'deleted' => '0',
      ],
    ];
    $tests[0]['source_data']['field_config'] = [
      [
        'id' => '2',
        'field_name' => 'size',
        'type' => 'taxonomy_term_reference',
        'module' => 'taxonomy',
        'active' => '1',
        'storage_type' => 'field_sql_storage',
        'storage_module' => 'field_sql_storage',
        'storage_active' => '1',
        'locked' => '0',
        'data' => 'a:6:{s:12:"entity_types";a:0:{}s:7:"indexes";a:1:{s:3:"tid";a:1:{i:0;s:3:"tid";}}s:8:"settings";a:2:{s:14:"allowed_values";a:1:{i:0;a:2:{s:10:"vocabulary";s:4:"size";s:6:"parent";i:0;}}s:21:"options_list_callback";s:29:"title_taxonomy_allowed_values";}s:12:"translatable";i:0;s:7:"storage";a:4:{s:4:"type";s:17:"field_sql_storage";s:8:"settings";a:0:{}s:6:"module";s:17:"field_sql_storage";s:6:"active";i:1;}s:12:"foreign keys";a:1:{s:3:"tid";a:2:{s:5:"table";s:18:"taxonomy_term_data";s:7:"columns";a:1:{s:3:"tid";s:3:"tid";}}}}',
        'cardinality' => '1',
        'translatable' => '0',
        'deleted' => '0',
      ],
    ];
    $tests[0]['source_data']['taxonomy_vocabulary'] = [
      [
        'vid' => 1,
        'name' => 'Tags',
        'description' => 'Tags description.',
        'hierarchy' => 0,
        'module' => 'taxonomy',
        'weight' => 0,
        'machine_name' => 'tags',
        'attribute' => FALSE,
      ],
      [
        'vid' => 2,
        'name' => 'Size',
        'description' => 'Item size.',
        'hierarchy' => 1,
        'module' => 'taxonomy',
        'weight' => 0,
        'machine_name' => 'size',
        'attribute' => TRUE,
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = $tests[0]['source_data']['taxonomy_vocabulary'];

    return $tests;
  }

}
