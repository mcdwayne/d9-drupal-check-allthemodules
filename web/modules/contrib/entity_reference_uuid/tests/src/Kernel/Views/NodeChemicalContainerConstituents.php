<?php

namespace Drupal\Tests\entity_reference_uuid\Kernel\Views;

use Drupal\views\Views;

/**
 * A test.
 *
 * @group entity_reference_uuid
 */
class NodeChemicalContainerConstituents extends UuidViewsKernelTestBase {

  /**
   * Views to be enabled.
   *
   * @var array
   */
  public static $testViews = [
    'node_forward_container_constituents',
    'node_forward_entity_reverse_constituents',
  ];

  public function testConstituentView() {
    // Validate the presave hook fixed all the test_nodetype_chemical values
    // where needed.
    $chemicals = $this->entityTypeManager->getStorage('node')->loadByProperties(['type' => 'test_nodetype_chemical']);
    foreach ($chemicals as $node) {
      $this->assertEquals($node->uuid(), $node->field_chemical_constituents->first()->target_uuid);
      if (count($node->field_chemical_constituents) == 1) {
        $this->assertEmpty($node->field_chemical_mixture_flag->value);
      }
      else {
        $this->assertNotEmpty($node->field_chemical_mixture_flag->value);
      }
    }
    $view = Views::getView('node_forward_container_constituents');
    $this->executeView($view);
    // There are 4 test_nodetype_two nodes that relate to 3 chemical nodes
    // that have 1, 2, 3, and 1 constituent which sums to 7 results.
    $this->assertCount(7, $view->result);
    // Create a test_nodetype_two that relates to "Ethanol".
    $test_nodetype_two = [
      [
        'uuid' => '0bc3f7f2-6dcd-4475-8d4c-e1acf8d0f75b',
        'title' => 'Container of Ethanol',
        'field_node_one_ref' => [
          [
            'target_uuid' => '2bbc790c-d3be-45c3-988c-660c4216be52',
          ],
        ],
      ],
    ];
    $this->createNodes('test_nodetype_two', $test_nodetype_two);
    $view = Views::getView('node_forward_container_constituents');
    $this->executeView($view);
    // There should be one more result.
    $this->assertCount(8, $view->result);
    $expected_uuids = [
      '29f515b1-90aa-4cec-8aeb-9798aa9afb46',
      'cea1815d-b027-48ea-810c-e79edbad75c0',
      'cea1815d-b027-48ea-810c-e79edbad75c0',
      '84ac71d4-fbbf-460b-b8a3-2d93e6191e46',
      '84ac71d4-fbbf-460b-b8a3-2d93e6191e46',
      '84ac71d4-fbbf-460b-b8a3-2d93e6191e46',
      'd46a21c5-8cb3-4d3e-9d24-8c0136b4d0eb',
      '0bc3f7f2-6dcd-4475-8d4c-e1acf8d0f75b',
    ];
    foreach ($view->result as $index => $row) {
      $this->assertEquals($expected_uuids[$index], $row->_entity->uuid());
    }
  }

  public function testReverseConstituentView() {
    $view = Views::getView('node_forward_entity_reverse_constituents');
    $this->executeView($view);
    // In the chemical fixtures "Acetone" and "Ammonium Nitrate" are related
    // from a test_entity_two. The "Container of Acetone" and
    // "Container of Acetone in Water" have "Acetone" as a constituent and the
    // "Container of Cold packs" and "Container of Ammonium Nitrate" have
    // "Ammonium Nitrate" as a constituent. Thus 4 results are expected.
    $this->assertCount(4, $view->result);
    // Create a test_nodetype_two that relates to "Ethanol".
    $test_nodetype_two = [
      [
        'uuid' => '0bc3f7f2-6dcd-4475-8d4c-e1acf8d0f75b',
        'title' => 'Container of Ethanol',
        'field_node_one_ref' => [
          [
            'target_uuid' => '2bbc790c-d3be-45c3-988c-660c4216be52',
          ],
        ],
      ],
    ];
    $this->createNodes('test_nodetype_two', $test_nodetype_two);
    $view = Views::getView('node_forward_entity_reverse_constituents');
    $this->executeView($view);
    // The results should be unchanged since there is no relationship from
    // a test_entity_two to "Ethanol"
    $this->assertCount(4, $view->result);
    // Make a relationship between "Ethanol" and "Safety data one one".
    $test_entity_two = [
      [
        'uuid' => 'd7655fea-1654-49d5-aebd-5d829e69287a',
        'name' => 'Safety data related one',
        'entity_one_ref' => [
          [
            'target_uuid' => 'bdd04085-3a7e-4334-9bd1-4ce9ce650152',
          ],
        ],
        'node_one_ref' => [
          [
            'target_uuid' => '2bbc790c-d3be-45c3-988c-660c4216be52',
          ],
        ],
      ],
    ];
    $this->createEntities('test_entity_two', $test_entity_two);
    $view = Views::getView('node_forward_entity_reverse_constituents');
    $this->executeView($view);
    $this->assertCount(5, $view->result);

    // Filter down to "Acetone" and "Ethanol" constituents based on the
    // related test_entity_one.
    $view = Views::getView('node_forward_entity_reverse_constituents');
    $view->setExposedInput(['entity_one_ref' => 'bdd04085-3a7e-4334-9bd1-4ce9ce650152']);
    $this->executeView($view);
    $this->assertCount(3, $view->result);
    $expected_uuids = [
      '29f515b1-90aa-4cec-8aeb-9798aa9afb46',
      'cea1815d-b027-48ea-810c-e79edbad75c0',
      '0bc3f7f2-6dcd-4475-8d4c-e1acf8d0f75b',
    ];
    foreach ($view->result as $index => $row) {
      $this->assertEquals($expected_uuids[$index], $row->_entity->uuid());
    }
  }

  /**
   * We want some different fixtures for these tests.
   */
  protected function setUpFixtures() {
    $test_nodetype_chemical = [
      [
        'uuid' => '825f0d30-23e1-4cb8-a9a3-d2266c0e6e65',
        'title' => 'Acetone',
        'field_chemical_cas_number' => '67-64-1',
      ],
      [
        'uuid' => '73b92228-fd2d-4b50-8578-31cc7aec7355',
        'title' => 'Acetone in Water',
        'field_chemical_constituents' => [
          [
            'target_uuid' => '825f0d30-23e1-4cb8-a9a3-d2266c0e6e65',
          ],
        ],
      ],
      [
        'uuid' => '2d88d540-7efd-47b0-9ac8-aa111cfc5f6c',
        'title' => 'Ammonium Nitrate',
        'field_chemical_cas_number' => '6484-52-2',
      ],
      [
        'uuid' => '92dcba07-4966-46e1-ab99-4e20d6674eb7',
        'title' => 'Dihydrogen oxide',
        'body' => [
          'value' => 'Water!',
          'summary' => '',
          'format' => 'plain_text',
        ],
        'field_chemical_cas_number' => '7732-18-5',
        // This should be fixed by the presave hook.
        'field_chemical_mixture_flag' => TRUE,
      ],
      [
        'uuid' => 'fa9c7f46-4b31-4f3c-987f-ad7b0fea0365',
        'title' => 'Instant cold pack',
        'body' => [
          'value' => 'https://en.wikipedia.org/wiki/Ice_pack',
          'summary' => '',
          'format' => 'plain_text',
        ],
        'field_chemical_constituents' => [
          [
            'target_uuid' => '2d88d540-7efd-47b0-9ac8-aa111cfc5f6c',
          ],
          [
            'target_uuid' => '92dcba07-4966-46e1-ab99-4e20d6674eb7',
          ],
        ],
        // This should be fixed by the presave hook.
        'field_chemical_mixture_flag' => FALSE,
      ],
      [
        'uuid' => '2bbc790c-d3be-45c3-988c-660c4216be52',
        'title' => 'Ethanol',
        'body' => [
          'value' => 'Essential nutrient for Drupal developers.

CH3CH2OH',
          'summary' => '',
          'format' => 'plain_text',
        ],
        'field_chemical_cas_number' => '64-17-5',
      ],
    ];
    $this->createNodes('test_nodetype_chemical', $test_nodetype_chemical);

    $test_nodetype_two = [
      [
        'uuid' => '29f515b1-90aa-4cec-8aeb-9798aa9afb46',
        'title' => 'Container of Acetone',
        'field_node_one_ref' => [
          [
            'target_uuid' => '825f0d30-23e1-4cb8-a9a3-d2266c0e6e65',
          ],
        ],
      ],
      [
        'uuid' => 'cea1815d-b027-48ea-810c-e79edbad75c0',
        'title' => 'Container of Acetone in Water',
        'field_node_one_ref' => [
          [
            'target_uuid' => '73b92228-fd2d-4b50-8578-31cc7aec7355',
          ],
        ],
      ],
      [
        'uuid' => '84ac71d4-fbbf-460b-b8a3-2d93e6191e46',
        'title' => 'Container of Cold packs',
        'field_node_one_ref' => [
          [
            'target_uuid' => 'fa9c7f46-4b31-4f3c-987f-ad7b0fea0365',
          ],
        ],
      ],
      [
        'uuid' => 'd46a21c5-8cb3-4d3e-9d24-8c0136b4d0eb',
        'title' => 'Container of Ammonium Nitrate',
        'field_node_one_ref' => [
          [
            'target_uuid' => '2d88d540-7efd-47b0-9ac8-aa111cfc5f6c',
          ],
        ],
      ],
    ];
    $this->createNodes('test_nodetype_two', $test_nodetype_two);

    $test_entity_one = [
      [
        'uuid' => 'bdd04085-3a7e-4334-9bd1-4ce9ce650152',
        'name' => 'Safety data one one',
      ],
      [
        'uuid' => '320f956d-b8d8-457b-a6d1-33976d5d4a14',
        'name' => 'Safety data one two',
      ],
      [
        'uuid' => '9632d9ef-1904-4394-b28e-20fa045dbea3',
        'name' => 'Safety data one three',
      ],
    ];
    $this->createEntities('test_entity_one', $test_entity_one);

    // For this test, we use test_nodetype_two to define a relationship between
    // a chemical constituent and a safety data record represented by a
    // test_entity_one.
    $test_entity_two = [
      [
        'uuid' => '964325bd-122e-4b24-b792-cd47a3b56596',
        'name' => 'Safety data related one',
        'entity_one_ref' => [
          [
            'target_uuid' => 'bdd04085-3a7e-4334-9bd1-4ce9ce650152',
          ],
        ],
        'node_one_ref' => [
          [
            'target_uuid' => '825f0d30-23e1-4cb8-a9a3-d2266c0e6e65',
          ],
        ],
      ],
      [
        'uuid' => 'b8017142-a58f-4e6a-a29b-ce120f4dae4b',
        'name' => 'Safety data related two',
        'entity_one_ref' => [
          [
            'target_uuid' => '320f956d-b8d8-457b-a6d1-33976d5d4a14',
          ],
        ],
        'node_one_ref' => [
          [
            'target_uuid' => '2d88d540-7efd-47b0-9ac8-aa111cfc5f6c',
          ],
        ],
      ],
    ];
    $this->createEntities('test_entity_two', $test_entity_two);
  }
}
