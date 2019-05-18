<?php

namespace Drupal\Tests\bynder\Functional;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests the Bynder formatter.
 *
 * @group bynder
 */
class BynderFormatterTest extends BrowserTestBase {

  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'bynder',
    'media',
    'node',
    'field_ui',
    'bynder_test_module',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalLogin($this->drupalCreateUser([
      'view media',
      'administer content types',
      'administer node display',
      'administer media types',
      'administer media display',
      'create media',
      'administer media form display',
      'administer node form display',
      'view bynder media usage',
    ]));

    $this->createContentType(['type' => 'page']);
    $entities = ['node' => 'page', 'media' => 'media_type'];

    // Attach fields to node and media entities to test is applicable logic.
    foreach ($entities as $entity => $bundle) {
      foreach (['string', 'string_long', 'entity_reference'] as $type) {
        $settings = $type == 'entity_reference' ? ['target_type' => 'media'] : [];
        \Drupal::entityTypeManager()->getStorage('field_storage_config')
          ->create([
            'field_name' => 'field_' . $type,
            'entity_type' => $entity,
            'type' => $type,
            'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
            'settings' => $settings,
          ])->save();

        $settings = $type == 'entity_reference' ? [
          'handler' => 'default:media',
          'handler_settings' => [
            'target_bundles' => [
              'media_type' => 'media_type',
            ],
          ],
        ] : [];
        \Drupal::entityTypeManager()->getStorage('field_config')
          ->create([
            'entity_type' => $entity,
            'bundle' => $bundle,
            'field_name' => 'field_' . $type,
            'label' => $this->randomMachineName(),
            'settings' => $settings,
          ])->save();
      }
    }

  }

  /**
   * Tests the Bynder formatter.
   */
  public function testBynderFormatter() {
    \Drupal::state()->set('bynder.bynder_test_derivatives', []);
    $this->drupalGet('admin/structure/types/manage/page/display');
    $this->assertSession()->optionExists('fields[field_entity_reference][type]', 'bynder');
    $this->assertSession()->optionNotExists('fields[field_string][type]', 'bynder');
    $this->assertSession()->optionNotExists('fields[field_string_long][type]', 'bynder');

    $edit = [
      'fields[field_entity_reference][type]' => 'bynder',
      'fields[field_entity_reference][region]' => 'content',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    $this->assertSession()->pageTextContains('Derivative: webimage');
    $this->getSession()->getPage()->pressButton('field_entity_reference_settings_edit');
    $this->assertSession()->pageTextContains('Select the name of the derivative to be used to display the image. Besides custom derivatives that you created in Bynder there are also default thumbnail sizes available that can be used.');
    $this->assertSession()->pageTextContains('Select the name of the field that should be used for the "title" attribute of the image.');
    $this->assertSession()->pageTextContains('Select the name of the field that should be used for the "alt" attribute of the image.');
    $this->assertSession()->linkExists('Bynder configuration form');
    // Assert select with default derivatives.
    $this->assertSession()->selectExists('fields[field_entity_reference][settings_edit_form][settings][thumbnail]');
    $this->assertSession()->optionExists('fields[field_entity_reference][settings_edit_form][settings][thumbnail]', 'mini');
    $this->assertSession()->optionExists('fields[field_entity_reference][settings_edit_form][settings][thumbnail]', 'webimage');
    $this->assertSession()->optionExists('fields[field_entity_reference][settings_edit_form][settings][thumbnail]', 'thul');
    $this->assertSession()->optionNotExists('fields[field_entity_reference][settings_edit_form][settings][thumbnail]', 'LinkedIn');
    // Assert select with attributes fields.
    $this->assertSession()->selectExists('fields[field_entity_reference][settings_edit_form][settings][alt_field]');
    $this->assertSession()->selectExists('fields[field_entity_reference][settings_edit_form][settings][title_field]');
    $this->assertSession()->optionExists('fields[field_entity_reference][settings_edit_form][settings][alt_field]', 'name');
    $this->assertSession()->optionExists('fields[field_entity_reference][settings_edit_form][settings][alt_field]', 'field_string');
    $this->assertSession()->optionExists('fields[field_entity_reference][settings_edit_form][settings][alt_field]', 'field_string_long');
    $this->assertSession()->optionExists('fields[field_entity_reference][settings_edit_form][settings][title_field]', 'name');
    $this->assertSession()->optionExists('fields[field_entity_reference][settings_edit_form][settings][title_field]', 'field_string');
    $this->assertSession()->optionExists('fields[field_entity_reference][settings_edit_form][settings][title_field]', 'field_string_long');
    // Choose attrbute fields.
    $this->getSession()->getPage()->selectFieldOption('fields[field_entity_reference][settings_edit_form][settings][alt_field]', 'name');
    $this->getSession()->getPage()->selectFieldOption('fields[field_entity_reference][settings_edit_form][settings][title_field]', 'field_string');
    $this->getSession()->getPage()->pressButton('field_entity_reference_plugin_settings_update');
    $this->getSession()->getPage()->pressButton('Save');

    // Change derivatives a bit.

    \Drupal::state()->set('bynder.bynder_test_derivatives', ['LinkedIn' => ['prefix' => 'LinkedIn']]);
    // Assert new derivatives options in select.
    $this->drupalGet('admin/structure/types/manage/page/display');
    $this->getSession()->getPage()->pressButton('field_entity_reference_settings_edit');
    $this->assertSession()->optionExists('fields[field_entity_reference][settings_edit_form][settings][thumbnail]', 'mini');
    $this->assertSession()->optionExists('fields[field_entity_reference][settings_edit_form][settings][thumbnail]', 'webimage');
    $this->assertSession()->optionExists('fields[field_entity_reference][settings_edit_form][settings][thumbnail]', 'thul');
    $this->assertSession()->optionExists('fields[field_entity_reference][settings_edit_form][settings][thumbnail]', 'LinkedIn');
    // Assert select with attributes fields.
    $this->assertSession()->selectExists('fields[field_entity_reference][settings_edit_form][settings][alt_field]');
    $this->assertSession()->selectExists('fields[field_entity_reference][settings_edit_form][settings][title_field]');
    $this->assertSession()->optionExists('fields[field_entity_reference][settings_edit_form][settings][alt_field]', 'name');
    $this->assertSession()->optionExists('fields[field_entity_reference][settings_edit_form][settings][alt_field]', 'field_string');
    $this->assertSession()->optionExists('fields[field_entity_reference][settings_edit_form][settings][alt_field]', 'field_string_long');
    $this->assertSession()->optionExists('fields[field_entity_reference][settings_edit_form][settings][title_field]', 'name');
    $this->assertSession()->optionExists('fields[field_entity_reference][settings_edit_form][settings][title_field]', 'field_string');
    $this->assertSession()->optionExists('fields[field_entity_reference][settings_edit_form][settings][title_field]', 'field_string_long');

    $this->drupalGet('admin/structure/types/manage/page/form-display');
    $this->drupalPostForm(NULL, ['fields[field_entity_reference][region]' => 'content'], t('Save'));

    $this->drupalGet('admin/structure/media/manage/media_type/display');
    $this->assertSession()->optionExists('fields[field_entity_reference][type]', 'bynder');
    $this->assertSession()->optionNotExists('fields[field_string][type]', 'bynder');
    $this->assertSession()->optionNotExists('fields[field_string_long][type]', 'bynder');

    $images = $this->getTestFiles('image');
    $bynder_data = [
      'type' => 'image',
      'id' => '123',
      'name' => 'Bynder name',
      'thumbnails' => [
        'mini' => file_create_url($images[0]->uri),
        'webimage' => file_create_url($images[1]->uri),
        'thul' => file_create_url($images[2]->uri),
      ],
      'propertyOptions' => [
        0 => "6EF40BA8-E011-4758-80C12BDCA70DDF4F",
      ],
    ];

    \Drupal::state()->set('bynder.bynder_test_media_info', $bynder_data);

    $media = \Drupal::entityTypeManager()->getStorage('media')->create([
      'name' => 'Media name test',
      'field_media_uuid' => '123',
      'bundle' => 'media_type',
      'type' => 'bynder',
      'field_string' => 'This will be title attribute',
    ]);
    $media->save();

    $node = \Drupal::entityTypeManager()->getStorage('node')->create([
      'title' => 'Page title',
      'field_entity_reference' => $media->id(),
      'type' => 'page',
    ]);
    $node->save();

    $this->drupalGet('node/' . $node->id());

    // With default formatter settings we should get the webimage.
    $this->assertSession()->responseContains('files/' . $images[1]->name);
    $this->assertSession()->responseContains('title="This will be title attribute"');
    $this->assertSession()->responseContains('alt="Media name test"');
    $this->assertSession()->responseContains('Usage info is not available yet. Usage restriction level: N/A');

    \Drupal::configFactory()->getEditable('bynder.settings')
      ->set('usage_metaproperty', '6EF40BA8-E011-4758-80C12BDCA1111111')
      ->set('restrictions.royalty_free', '6EF40BA8-E011-4758-80C12BDCA70DDF4F')
      ->set('restrictions.web_license', '6EF40BA8-E011-4758-80C12BDCA2222222')
      ->set('restrictions.print_license', '6EF40BA8-E011-4758-80C12BDCA3333333')
      ->save(TRUE);

    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->responseContains('Usage info is not available yet. Usage restriction level: Royality free');
    $this->assertSession()->responseNotContains('Usage info is not available yet. Usage restriction level: N/A');

    \Drupal::configFactory()->getEditable('bynder.settings')
      ->set('restrictions.royalty_free', '6EF40BA8-E011-4758-80C12BDCA2222222')
      ->set('restrictions.web_license', '6EF40BA8-E011-4758-80C12BDCA70DDF4F')
      ->save(TRUE);

    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->responseNotContains('Usage info is not available yet. Usage restriction level: Royality free');
    $this->assertSession()->responseNotContains('Usage info is not available yet. Usage restriction level: N/A');
    $this->assertSession()->responseContains('Usage info is not available yet. Usage restriction level: Web licence');

    $this->drupalLogin($this->drupalCreateUser([
      'view media',
      'administer content types',
      'administer node display',
      'administer media types',
      'administer media display',
      'create media',
      'administer media form display',
      'administer node form display',
    ], 'Test User'));

    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->responseNotContains('Usage info is not available yet. Usage restriction level: N/A');

    $this->drupalGet('admin/structure/types/manage/page/display');
    $this->getSession()->getPage()->pressButton('field_entity_reference_settings_edit');
    $this->getSession()->getPage()->fillField('fields[field_entity_reference][settings_edit_form][settings][thumbnail]', 'mini');
    $this->getSession()->getPage()->selectFieldOption('fields[field_entity_reference][settings_edit_form][settings][alt_field]', 'name');
    $this->getSession()->getPage()->selectFieldOption('fields[field_entity_reference][settings_edit_form][settings][title_field]', 'field_string');
    $this->getSession()->getPage()->pressButton('field_entity_reference_plugin_settings_update');
    $this->getSession()->getPage()->pressButton('Save');

    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->responseContains('files/' . $images[0]->name);
    $this->assertSession()->responseContains('title="This will be title attribute"');
    $this->assertSession()->responseContains('alt="Media name test"');

    $this->drupalGet('admin/structure/types/manage/page/display');
    $this->getSession()->getPage()->pressButton('field_entity_reference_settings_edit');
    $this->getSession()->getPage()->fillField('fields[field_entity_reference][settings_edit_form][settings][thumbnail]', 'thul');
    $this->getSession()->getPage()->selectFieldOption('fields[field_entity_reference][settings_edit_form][settings][alt_field]', 'name');
    $this->getSession()->getPage()->selectFieldOption('fields[field_entity_reference][settings_edit_form][settings][title_field]', 'field_string');
    $this->getSession()->getPage()->pressButton('field_entity_reference_plugin_settings_update');
    $this->getSession()->getPage()->pressButton('Save');

    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->responseContains('files/' . $images[2]->name);
    $this->assertSession()->responseContains('title="This will be title attribute"');
    $this->assertSession()->responseContains('alt="Media name test"');

    // Delete referenced media and make sure we are still able to see the node
    // page.
    $media->delete();
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->statusCodeEquals('200');
    $this->assertSession()->responseContains($node->label());
    $this->assertSession()->responseNotContains('files/' . $images[2]->name);
  }

}
