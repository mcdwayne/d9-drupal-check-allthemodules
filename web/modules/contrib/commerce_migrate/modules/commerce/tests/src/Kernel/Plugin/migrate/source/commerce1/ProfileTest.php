<?php

namespace Drupal\Tests\commerce_migrate_commerce\Kernel\Plugin\migrate\source\commerce1;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests the profile source plugin.
 *
 * @covers \Drupal\commerce_migrate_commerce\Plugin\migrate\source\commerce1\Profile
 *
 * @group commerce_migrate
 * @group commerce_migrate_commerce1
 */
class ProfileTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['migrate_drupal', 'commerce_migrate_commerce'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];
    // The source data.
    $tests[0]['source_data']['commerce_customer_profile'] = [
      [
        'profile_id' => '1',
        'revision_id' => '1',
        'type' => 'billing',
        'uid' => '5',
        'status' => '1',
        'created' => '1493287440',
        'changed' => '1493287450',
        'data' => 'a:1:{s:4:"ship";s:10:"white star";}',
      ],
      [
        'profile_id' => '2',
        'revision_id' => '3',
        'type' => 'shipping',
        'uid' => '5',
        'status' => '1',
        'created' => '1493287441',
        'changed' => '1493287551',
        'data' => 'a:1:{s:4:"ship";s:9:"andromeda";}',
      ],
      [
        'profile_id' => '4',
        'revision_id' => '4',
        'type' => 'other',
        'uid' => '2',
        'status' => '1',
        'created' => '1493287641',
        'changed' => '1493287641',
        'data' => 'a:1:{s:4:"ship";s:4:"moya";}',
      ],
    ];
    $tests[0]['source_data']['commerce_customer_profile_revision'] = [
      [
        'profile_id' => '1',
        'revision_id' => '1',
        'revision_uid' => '1',
        'status' => '8',
        'log' => '',
        'revision_timestamp' => '1493287450',
        'data' => 'a:1:{s:4:"ship";s:8:"serenity";}',
      ],
      [
        'profile_id' => '2',
        'revision_id' => '2',
        'revision_uid' => '1',
        'status' => '9',
        'log' => '',
        'revision_timestamp' => '1493287451',
        'data' => 'a:1:{s:4:"ship";s:7:"defiant";}',
      ],
      [
        'profile_id' => '2',
        'revision_id' => '3',
        'revision_uid' => '1',
        'status' => '10',
        'log' => '',
        'revision_timestamp' => '1493287551',
        'data' => 'a:1:{s:4:"ship";s:9:"red dwarf";}',
      ],
      [
        'profile_id' => '4',
        'revision_id' => '4',
        'revision_uid' => '1',
        'status' => '11',
        'log' => '',
        'revision_timestamp' => '1493287641',
        'data' => 'a:1:{s:4:"ship";s:9:"liberator";}',
      ],
    ];
    $tests[0]['source_data']['commerce_addressbook_defaults'] = [
      [
        'cad_id' => '1',
        'profile_id' => '1',
        'type' => 'billing',
        'uid' => '5',
      ],
      [
        'cad_id' => '2',
        'profile_id' => '2',
        'type' => 'shipping',
        'uid' => '5',
      ],
    ];
    $tests[0]['source_data']['field_config'] = [
      [
        'id' => '11',
        'field_name' => 'field_file',
        'type' => 'file',
        'module' => 'file',
        'active' => '1',
        'storage_type' => 'field_sql_storage',
        'storage_module' => 'field_sql_storage',
        'storage_active' => '1',
        'locked' => '0',
        'data' => 'a:7:{s:12:"translatable";s:1:"0";s:12:"entity_types";a:0:{}s:8:"settings";a:3:{s:13:"display_field";i:0;s:15:"display_default";i:0;s:10:"uri_scheme";s:6:"public";}s:7:"storage";a:5:{s:4:"type";s:17:"field_sql_storage";s:8:"settings";a:0:{}s:6:"module";s:17:"field_sql_storage";s:6:"active";s:1:"1";s:7:"details";a:1:{s:3:"sql";a:2:{s:18:"FIELD_LOAD_CURRENT";a:1:{s:21:"field_data_field_file";a:3:{s:3:"fid";s:14:"field_file_fid";s:7:"display";s:18:"field_file_display";s:11:"description";s:22:"field_file_description";}}s:19:"FIELD_LOAD_REVISION";a:1:{s:25:"field_revision_field_file";a:3:{s:3:"fid";s:14:"field_file_fid";s:7:"display";s:18:"field_file_display";s:11:"description";s:22:"field_file_description";}}}}}s:12:"foreign keys";a:1:{s:3:"fid";a:2:{s:5:"table";s:12:"file_managed";s:7:"columns";a:1:{s:3:"fid";s:3:"fid";}}}s:7:"indexes";a:1:{s:3:"fid";a:1:{i:0;s:3:"fid";}}s:2:"id";s:2:"11";}',
        'cardinality' => '1',
        'translatable' => '0',
        'deleted' => '0',
      ],
    ];
    $tests[0]['source_data']['field_config_instance'] = [
      [
        'id' => '1',
        'field_id' => '1',
        'field_name' => 'commerce_customer_address',
        'entity_type' => 'commerce_customer_profile',
        'bundle' => 'billing',
        'data' => 'a:6:{s:5:"label";s:7:"Address";s:8:"required";b:1;s:6:"widget";a:4:{s:4:"type";s:21:"addressfield_standard";s:6:"weight";i:-10;s:8:"settings";a:3:{s:15:"format_handlers";a:2:{i:0;s:7:"address";i:1;s:12:"name-oneline";}s:19:"available_countries";a:0:{}s:15:"default_country";s:12:"site_default";}s:6:"module";s:12:"addressfield";}s:7:"display";a:3:{s:7:"default";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:20:"addressfield_default";s:6:"weight";i:-10;s:8:"settings";a:2:{s:19:"use_widget_handlers";i:1;s:15:"format_handlers";a:1:{i:0;s:7:"address";}}s:6:"module";s:12:"addressfield";}s:8:"customer";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:20:"addressfield_default";s:6:"weight";i:-10;s:8:"settings";a:2:{s:19:"use_widget_handlers";i:1;s:15:"format_handlers";a:1:{i:0;s:7:"address";}}s:6:"module";s:12:"addressfield";}s:13:"administrator";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:20:"addressfield_default";s:6:"weight";i:-10;s:8:"settings";a:2:{s:19:"use_widget_handlers";i:1;s:15:"format_handlers";a:1:{i:0;s:7:"address";}}s:6:"module";s:12:"addressfield";}}s:8:"settings";a:1:{s:18:"user_register_form";b:0;}s:11:"description";s:0:"";}',
        'deleted' => '0',
      ],
      [
        'id' => '2',
        'field_id' => '2',
        'field_name' => 'commerce_customer_address',
        'entity_type' => 'commerce_customer_profile',
        'bundle' => 'shipping',
        'data' => 'a:6:{s:5:"label";s:7:"Address";s:8:"required";b:1;s:6:"widget";a:4:{s:4:"type";s:21:"addressfield_standard";s:6:"weight";i:-10;s:8:"settings";a:3:{s:15:"format_handlers";a:2:{i:0;s:7:"address";i:1;s:12:"name-oneline";}s:19:"available_countries";a:0:{}s:15:"default_country";s:12:"site_default";}s:6:"module";s:12:"addressfield";}s:7:"display";a:3:{s:7:"default";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:20:"addressfield_default";s:6:"weight";i:-10;s:8:"settings";a:2:{s:19:"use_widget_handlers";i:1;s:15:"format_handlers";a:1:{i:0;s:7:"address";}}s:6:"module";s:12:"addressfield";}s:8:"customer";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:20:"addressfield_default";s:6:"weight";i:-10;s:8:"settings";a:2:{s:19:"use_widget_handlers";i:1;s:15:"format_handlers";a:1:{i:0;s:7:"address";}}s:6:"module";s:12:"addressfield";}s:13:"administrator";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:20:"addressfield_default";s:6:"weight";i:-10;s:8:"settings";a:2:{s:19:"use_widget_handlers";i:1;s:15:"format_handlers";a:1:{i:0;s:7:"address";}}s:6:"module";s:12:"addressfield";}}s:8:"settings";a:1:{s:18:"user_register_form";b:0;}s:11:"description";s:0:"";}',
        'deleted' => '0',
      ],
    ];
    $tests[0]['source_data']['field_revision_commerce_customer_address'] = [
      [
        'entity_type' => 'commerce_customer_profile',
        'bundle' => 'billing',
        'deleted' => '0',
        'entity_id' => '1',
        'revision_id' => '1',
        'language' => 'und',
        'delta' => '0',
        'commerce_customer_address_country' => 'NZ',
        'commerce_customer_address_administrative_area' => 'Southland',
      ],
      [
        'entity_type' => 'commerce_customer_profile',
        'bundle' => 'shipping',
        'deleted' => '0',
        'entity_id' => '2',
        'revision_id' => '3',
        'language' => 'und',
        'delta' => '0',
        'commerce_customer_address_country' => 'US',
        'commerce_customer_address_administrative_area' => 'CA',
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = [
      [
        'profile_id' => '1',
        'revision_id' => '1',
        'type' => 'billing',
        'uid' => '5',
        'status' => '1',
        'created' => '1493287440',
        'changed' => '1493287450',
        'data' => unserialize('a:1:{s:4:"ship";s:10:"white star";}'),
        'revision_uid' => '1',
        'log' => '',
        'revision_timestamp' => '1493287450',
        'revision_status' => '8',
        'revision_data' => unserialize('a:1:{s:4:"ship";s:8:"serenity";}'),
        'cad_type' => 'billing',
        'commerce_customer_address' => [
          [
            'country' => 'NZ',
            'administrative_area' => 'Southland',
          ],
        ],
      ],
      [
        'profile_id' => '2',
        'revision_id' => '3',
        'type' => 'shipping',
        'uid' => '5',
        'status' => '1',
        'created' => '1493287441',
        'changed' => '1493287551',
        'data' => unserialize('a:1:{s:4:"ship";s:9:"andromeda";}'),
        'revision_uid' => '1',
        'log' => '',
        'revision_timestamp' => '1493287551',
        'revision_status' => '10',
        'revision_data' => unserialize('a:1:{s:4:"ship";s:9:"red dwarf";}'),
        'cad_type' => 'shipping',
        'commerce_customer_address' => [
          [
            'country' => 'US',
            'administrative_area' => 'CA',
          ],
        ],
      ],
    ];
    $tests[0]['expected_count'] = NULL;
    $tests[0]['configuration']['profile_type'] =
      [
        'billing',
        'shipping',
      ];

    // Repeat test0 without the commerce_addressbook_defaults table.
    $tests[1] = $tests[0];
    unset($tests[1]['source_data']['commerce_addressbook_defaults']);
    $tests[1]['expected_data'][0]['cad_type'] = NULL;
    $tests[1]['expected_data'][1]['cad_type'] = NULL;

    // Repeat test0 configuration profile_type as a string.
    $tests[2] = $tests[0];
    // The expected results.
    $tests[2]['expected_data'] = [
      [
        'profile_id' => '1',
        'revision_id' => '1',
        'type' => 'billing',
        'uid' => '5',
        'status' => '1',
        'created' => '1493287440',
        'changed' => '1493287450',
        'data' => unserialize('a:1:{s:4:"ship";s:10:"white star";}'),
        'revision_uid' => '1',
        'log' => '',
        'revision_timestamp' => '1493287450',
        'revision_status' => '8',
        'revision_data' => unserialize('a:1:{s:4:"ship";s:8:"serenity";}'),
        'cad_type' => 'billing',
        'commerce_customer_address' => [
          [
            'country' => 'NZ',
            'administrative_area' => 'Southland',
          ],
        ],
      ],
    ];
    $tests[2]['configuration']['profile_type'] = 'billing';
    return $tests;
  }

}
