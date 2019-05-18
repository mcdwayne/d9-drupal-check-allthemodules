<?php

namespace Drupal\Tests\bynder\Functional;

use Drupal\bynder\BynderApi;
use Drupal\bynder_test_module\BynderApiTest;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\media\Entity\MediaType;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Bynder usage tracking.
 *
 * @group bynder
 */
class UsageTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'media',
    'media_entity_generic',
    'path',
    'entity_usage',
    'bynder_test_module',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    MediaType::create([
      'id' => 'generic',
      'label' => 'Generic media bundle',
      'source' => 'generic',
    ])->save();

    $this->createContentType(['type' => 'reference']);

    // The type to host the reference.
    $this->createContentType(['type' => 'host']);

    // Add reference field to host content type.
    foreach (['node', 'media'] as $target_type) {
      \Drupal::entityTypeManager()->getStorage('field_storage_config')
        ->create([
          'field_name' => 'field_reference_' . $target_type,
          'entity_type' => 'node',
          'type' => 'entity_reference',
          'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
          'settings' => [
            'target_type' => $target_type,
          ],
        ])->save();

      \Drupal::entityTypeManager()->getStorage('field_config')
        ->create([
          'entity_type' => 'node',
          'bundle' => 'host',
          'field_name' => 'field_reference_' . $target_type,
          'label' => $target_type,
          'settings' => [
            'handler' => 'default:' . $target_type,
            'handler_settings' => [
              'target_bundles' => NULL,
            ],
          ],
        ])->save();

      /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display */
      $form_display = $this->container->get('entity_type.manager')
        ->getStorage('entity_form_display')
        ->load('node.host.default');

      $form_display->setComponent('field_reference_' . $target_type, [
        'type' => 'entity_reference_autocomplete',
      ])->save();

      $display = $this->container->get('entity_type.manager')
        ->getStorage('entity_view_display')
        ->load('node.host.default');

      $display->setComponent('field_reference_' . $target_type, [
        'type' => 'entity_reference_label',
      ])->save();
    }

    $this->drupalLogin($this->drupalCreateUser([
      'create host content',
      'edit any host content',
      'create url aliases',
      'administer url aliases',
    ]));
  }

  /**
   * Tests Bynder usage tracking.
   */
  public function testUsageTracking() {
    $reference_node = $this->createNode(['type' => 'reference', 'title' => 'Reference node']);

    $bynder_media = \Drupal::entityTypeManager()->getStorage('media')->create([
      'name' => 'Media bynder',
      'field_media_uuid' => '123',
      'bundle' => 'media_type',
      'type' => 'bynder',
    ]);
    $bynder_media->save();

    $generic_media = \Drupal::entityTypeManager()->getStorage('media')->create([
      'name' => 'Media generic',
      'bundle' => 'generic',
      'type' => 'generic',
    ]);
    $generic_media->save();

    // Test with a node as referenced entity.
    $this->drupalGet('node/add/host');
    $this->getSession()->getPage()->fillField('title[0][value]', 'Host node');
    $this->getSession()->getPage()->fillField('field_reference_node[0][target_id]', $reference_node->label() . ' (' . $reference_node->id() . ')');
    $this->getSession()->getPage()->pressButton('Save');

    $this->assertEmpty(\Drupal::state()->get('bynder.bynder_add_usage'), 'Add usage is not sent to Bynder for entities that are not of type Media.');

    $host_node = current(\Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['title' => 'Host node']));

    $this->drupalGet('admin/config/search/path/add');

    // Create alias.
    $edit['source'] = '/node/' . $host_node->id();
    $edit['alias'] = '/' . $this->randomMachineName(8);
    $this->drupalPostForm('admin/config/search/path/add', $edit, t('Save'));

    $this->drupalGet('node/' . $host_node->id() . '/edit');
    $this->getSession()->getPage()->fillField('field_reference_node[0][target_id]', '');
    $this->getSession()->getPage()->pressButton('Save');

    $this->assertEmpty(\Drupal::state()->get('bynder.bynder_delete_usage'), 'Remove usage is not sent to Bynder for entities that are not of type Media.');

    // Test with media as referenced entity with generic type provider.
    $this->drupalGet('node/' . $host_node->id() . '/edit');

    $this->getSession()->getPage()->fillField('field_reference_media[0][target_id]', $generic_media->label() . ' (' . $generic_media->id() . ')');
    $this->getSession()->getPage()->pressButton('Save');

    $this->assertEmpty(\Drupal::state()->get('bynder.bynder_add_usage'), 'Add usage is not send to Bynder for media entities where the type provider is not Bynder.');

    $this->drupalGet('node/' . $host_node->id() . '/edit');

    $this->getSession()->getPage()->fillField('field_reference_media[0][target_id]', '');
    $this->getSession()->getPage()->pressButton('Save');

    $this->assertEmpty(\Drupal::state()->get('bynder.bynder_delete_usage'), 'Remove usage is not sent to Bynder for media entities where the type provider is not of type Bynder.');

    // Test with media as referenced entity with bynder as type provider.
    $this->drupalGet('node/' . $host_node->id() . '/edit');

    $this->getSession()->getPage()->fillField('field_reference_media[0][target_id]', $bynder_media->label() . ' (' . $bynder_media->id() . ')');
    $this->getSession()->getPage()->pressButton('Save');

    $state = \Drupal::state()->get('bynder.bynder_add_usage');
    $this->assertEquals(BynderApi::BYNDER_INTEGRATION_ID, $state['integration_id']);
    $this->assertEquals($bynder_media->field_media_uuid->value, $state['asset_id']);
    $this->assertTrue(is_string($state['timestamp']));

    $url = \Drupal\Core\Url::fromRoute(
      'entity.node.canonical',
      ['node' => $host_node->id()],
      ['fragment' => 'node/' . $host_node->id()]
    )->setAbsolute(TRUE)->toString();
    $this->assertEquals($url, $state['location']->setAbsolute(TRUE)->toString());
    $this->assertEquals('Added asset by user ' . \Drupal::currentUser()->getAccountName() . '.', $state['additional']);

    $this->drupalGet('node/' . $host_node->id() . '/edit');

    $this->getSession()->getPage()->fillField('field_reference_media[0][target_id]', '');
    $this->getSession()->getPage()->pressButton('Save');

    $state = \Drupal::state()->get('bynder.bynder_delete_usage');
    $this->assertEquals(BynderApiTest::BYNDER_INTEGRATION_ID, $state['integration_id']);
    $this->assertEquals($bynder_media->field_media_uuid->value, $state['asset_id']);
  }

}
