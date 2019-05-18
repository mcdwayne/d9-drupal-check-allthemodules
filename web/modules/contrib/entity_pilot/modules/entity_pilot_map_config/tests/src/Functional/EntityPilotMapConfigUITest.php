<?php

namespace Drupal\Tests\entity_pilot_map_config\Functional;

use Drupal\entity_pilot\ArrivalInterface;
use Drupal\Tests\entity_pilot\Functional\ArrivalTestBase;
use Drupal\entity_pilot_map_config\Entity\BundleMapping;

/**
 * Tests map config UIs.
 *
 * @group entity_pilot
 */
class EntityPilotMapConfigUITest extends ArrivalTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing';

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_pilot',
    'serialization',
    'hal',
    'rest',
    'text',
    'node',
    'user',
    'system',
    'field',
    'file',
    'filter',
    'image',
    'entity_pilot_map_config',
    'entity_pilot_map_config_test',
    'filter',
  ];

  /**
   * {@inheritdoc}
   */
  protected $permissions = [
    'administer entity_pilot accounts',
    'access administration pages',
    'administer entity_pilot arrivals',
    'view test entity',
    'edit any post content',
    'administer entity_pilot bundle mappings',
    'administer entity_pilot field mappings',
  ];

  /**
   * {@inheritdoc}
   */
  protected $testBreadcrumbs = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $checkTermArrivals = FALSE;

  /**
   * Tests config mapping UI.
   */
  public function testArrivalWithMapping() {
    // Create an arrival.
    $arrival = $this->addAccountsAndArrival();
    // Check we get redirected to bundle mapping.
    $this->assertContains('/admin/structure/entity-pilot/bundle-mappings/flight_1_account_primary', $this->getUrl());
    $this->assertRaw(t('Please configure how to handle missing bundles.'));
    // Complete the bundle mapping form.
    $this->drupalPostForm(NULL, [
      'label' => 'http://example.com',
      'mappings[0][destination_bundle_name]' => BundleMappingUITest::BUNDLE_NAME,
      'mappings[1][destination_bundle_name]' => BundleMappingUITest::BUNDLE_NAME,
    ], t('Save'));
    // Check we get redirected to field mapping.
    $this->assertContains('/admin/structure/entity-pilot/field-mappings/flight_1_account_primary', $this->getUrl());
    $this->assertRaw(t('Please configure how to handle missing fields.'));
    // Complete the field mapping form.
    $this->drupalPostForm(NULL, [
      'label' => 'http://example.com',
      'mappings[2][destination_field_name]' => FieldMappingUITest::FIELD_NAME,
    ], t('Save'));
    $this->doTestMappedArrivalApproval($arrival);

  }

  /**
   * Test when multiple mappings exist.
   */
  public function testMultipleMappings() {
    // Create some bundle mappings.
    $mapping_values = [
      [
        'entity_type' => 'node',
        'source_bundle_name' => 'page',
        'destination_bundle_name' => 'post',
      ],
      [
        'entity_type' => 'node',
        'source_bundle_name' => 'article',
        'destination_bundle_name' => 'post',
      ],
    ];
    $mapping_1 = BundleMapping::create([
      'id' => 'mapping_1',
      'label' => 'Mapping 1',
      'mappings' => $mapping_values,
    ]);
    $mapping_1->save();
    $mapping_2 = BundleMapping::create([
      'id' => 'mapping_2',
      'label' => 'Mapping 2',
      'mappings' => $mapping_values,
    ]);
    $mapping_2->save();
    array_pop($mapping_values);
    $mapping_3 = BundleMapping::create([
      'id' => 'mapping_3',
      'label' => 'Mapping 3',
      'mappings' => $mapping_values,
    ]);
    $mapping_3->save();
    // Create an arrival.
    $arrival = $this->addAccountsAndArrival();
    // Check we get redirected to field mapping.
    $this->assertContains('/admin/structure/entity-pilot/field-mappings/flight_1_account_primary', $this->getUrl());

    // Complete the field mapping form.
    $this->drupalPostForm(NULL, [
      'label' => 'http://example.com',
      'mappings[2][destination_field_name]' => FieldMappingUITest::FIELD_NAME,
    ], t('Save'));
    // Now we should be on selection form.
    $this->assertContains('/admin/structure/entity-pilot/arrivals/' . $arrival->id() . '/mapping', $this->getUrl());
    $this->assertFieldByName('mapping_fields');
    $this->assertFieldByName('mapping_bundles');
    $this->assertOption('edit-mapping-bundles', 'mapping_1');
    $this->assertOption('edit-mapping-bundles', 'mapping_2');
    $this->assertNoOption('edit-mapping-bundles', 'mapping_3');
    // Approved passengers field should not be visible.
    $this->assertNoFieldByName('approved_passengers[505718d0-d918-4474-ba02-2c4108b4c7aa]');
    // Submit form.
    $this->drupalPostForm(NULL, [], t('Save'));
    $this->checkForMetaRefresh();
    // Now should be ready to approve.
    $this->doTestMappedArrivalApproval($arrival);
  }

  /**
   * Tests the mapped arrival can be landed appropriately.
   *
   * @param \Drupal\entity_pilot\ArrivalInterface $arrival
   *   Arrival under test.
   */
  protected function doTestMappedArrivalApproval(ArrivalInterface $arrival) {
    // We should now be on the approve form.
    $this->assertUrl('admin/structure/entity-pilot/arrivals/' . $arrival->id() . '/approve?');
    $this->drupalGet('admin/structure/entity-pilot/arrivals/' . $arrival->id() . '/approve/preview/' . self::ARTICLE_UUID);
    $this->assertText('Pickled Schlitz fixie, butcher forage');
    $this->assertText('Submitted by');
    $image = $this->cssSelect('.node .field--name-field-images img');
    $this->assertTrue(preg_match('/hazelnuts-small/', $image[0]->getAttribute('src')));
    // Return to edit/approve.
    $this->drupalGet('admin/structure/entity-pilot/arrivals/' . $arrival->id() . '/approve');
    // Approval all bar the admin account.
    $edit = [
      'approved_passengers[5f1af923-22f8-4799-9204-4f6f030bd879]' => 1,
      'approved_passengers[de511610-ae97-49a2-b65f-9548e54df2fa]' => 1,
      'approved_passengers[ea15274d-949c-4238-902d-45ca3c828ed1]' => 1,
      'approved_passengers[01f1b727-d660-4647-8439-57be4e9cfce7]' => 1,
      'approved_passengers[7bec3ab2-cc87-488e-a607-7d70fb243e5f]' => 1,
      'approved_passengers[82c0651e-9bf9-4de7-9800-be1d6a5ae5a4]' => 1,
      'approved_passengers[8384692b-379c-4067-b000-bea20ef3aaca]' => 1,
      'link_departure' => 1,
    ];
    $this->drupalPostForm(NULL, $edit, t('Approve & Land'));
    $this->checkForMetaRefresh();
    $this->assertText('Arrival for Primary account named Spring content refresh has been updated.');
    $this->doArrivalTests($arrival, 'field_images');
  }

}
