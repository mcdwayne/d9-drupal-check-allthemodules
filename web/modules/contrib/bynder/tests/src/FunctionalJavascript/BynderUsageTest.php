<?php

namespace Drupal\Tests\bynder\FunctionalJavascript;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\user\Entity\Role;

/**
 * Test the Bynder media usage info.
 *
 * @group bynder
 */
class BynderUsageTest extends JavascriptTestBase {

  /**
   * User for testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $testUser;

  /**
   * Role for testing.
   *
   * @var \Drupal\user\RoleInterface
   */
  protected $testRole;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'bynder',
    'media',
    'bynder_test_module',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->createContentType(['type' => 'page']);

    $entities = [
      'node' => 'page',
      'media' => 'media_type',
    ];

    foreach ($entities as $entity => $bundle) {
      foreach (['string', 'string_long', 'entity_reference'] as $type) {
        $settings = $type == 'entity_reference' ? ['target_type' => 'media'] : [];
        \Drupal::entityTypeManager()->getStorage('field_storage_config')
          ->create(
            [
              'field_name' => 'field_' . $type,
              'entity_type' => $entity,
              'type' => $type,
              'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
              'settings' => $settings,
            ]
          )->save();

        $settings = $type == 'entity_reference' ? [
          'handler' => 'default:media',
          'handler_settings' => [
            'target_bundles' => [
              'media_type' => 'media_type',
            ],
          ],
        ] : [];
        \Drupal::entityTypeManager()->getStorage('field_config')
          ->create(
            [
              'entity_type' => $entity,
              'bundle' => $bundle,
              'field_name' => 'field_' . $type,
              'label' => $this->randomMachineName(),
              'settings' => $settings,
            ]
          )->save();
      }
    }

    $this->testRole = Role::create(['id' => 'editor']);
    $this->testRole->grantPermission('access content');
    $this->testRole->save();

    $this->testUser = $this->drupalCreateUser();
    $this->testUser->addRole($this->testRole->id());
    $this->testUser->save();

    $this->drupalLogin($this->testUser);
  }

  /**
   * Test Bynder media usage info.
   */
  public function testBynderUsage() {
    $first_node = \Drupal::entityTypeManager()->getStorage('node')->create(
      [
        'title' => 'First node title',
        'field_entity_reference' => [],
        'type' => 'page',
      ]
    );
    $first_node->save();
    $this->drupalGet('node/' . $first_node->id() . '/usage');
    // Test access is forbidden without permission.
    $this->assertSession()->statusCodeEquals(403);
    $this->assertSession()->responseContains('Access denied');
    $this->assertSession()->responseContains(
      'You are not authorized to access this page.'
    );

    $this->testRole->grantPermission('view bynder media usage');
    $this->testRole->save();

    // Go on Bynder media usage tab and test message when table is empty.
    $this->drupalGet('node/' . $first_node->id() . '/usage');
    $this->assertSession()->responseContains(
      'There are no Bynder media found on the page.'
    );

    \Drupal::configFactory()->getEditable('bynder.settings')
      ->set('account_domain', 'https://plugin.getbynder.com/')
      ->save(TRUE);

    $bynder_data = [
      'type' => 'image',
      'id' => '123',
      'name' => 'Bynder name',
      'propertyOptions' => [
        0 => "6EF40BA8-E011-4758-80C12BDCA70DDF4F",
      ],
    ];

    \Drupal::state()->set('bynder.bynder_test_media_info', $bynder_data);

    $media = \Drupal::entityTypeManager()->getStorage('media')->create(
      [
        'name' => 'Media name test',
        'field_media_uuid' => '123',
        'bundle' => 'media_type',
        'type' => 'bynder',
      ]
    );

    $media->save();

    $node = \Drupal::entityTypeManager()->getStorage('node')->create(
      [
        'title' => 'Page title',
        'field_entity_reference' => $media->id(),
        'type' => 'page',
      ]
    );
    $node->save();
    // Assert referenced Bynder media appears on Bynder media usage tab.
    $this->drupalGet('node/' . $node->id() . '/usage');
    $this->assertSession()->responseContains($bynder_data['name']);
    $this->assertSession()->responseContains($bynder_data['type']);
    $this->assertSession()->responseContains('N/A');
    $this->assertSession()->linkExists('Edit');
    $this->assertSession()->linkByHrefExists(
      '/media/' . $media->id() . '/edit'
    );
    $this->assertSession()->linkExists('Edit on Bynder');
    $this->assertSession()->linkByHrefExists(
      '/media?mediaId=' . $bynder_data['id']
    );

    \Drupal::configFactory()->getEditable('bynder.settings')
      ->set('usage_metaproperty', '6EF40BA8-E011-4758-80C12BDCA1111111')
      ->set('restrictions.royalty_free', '6EF40BA8-E011-4758-80C12BDCA70DDF4F')
      ->set('restrictions.web_license', '6EF40BA8-E011-4758-80C12BDCA2222222')
      ->set('restrictions.print_license', '6EF40BA8-E011-4758-80C12BDCA3333333')
      ->save(TRUE);

    $this->drupalGet('node/' . $node->id() . '/usage');
    $this->assertSession()->responseContains('Royality free');
    $this->assertSession()->responseNotContains('N/A');

    \Drupal::configFactory()->getEditable('bynder.settings')
      ->set('restrictions.royalty_free', '6EF40BA8-E011-4758-80C12BDCA2222222')
      ->set('restrictions.web_license', '6EF40BA8-E011-4758-80C12BDCA70DDF4F')
      ->save(TRUE);

    $this->drupalGet('node/' . $node->id() . '/usage');
    $this->assertSession()->responseNotContains('Royality free');
    $this->assertSession()->responseNotContains('N/A');
    $this->assertSession()->responseContains('Web licence');
  }

}
