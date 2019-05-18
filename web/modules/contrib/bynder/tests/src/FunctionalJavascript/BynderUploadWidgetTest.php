<?php
/**
 * @file
 * Tests Bynder upload widget.
 *
 * Notice: There is no way to ensure DropzoneJS library is added to the test
 * environment on Drupal.org. We might want to re-enable this test later or keep
 * it here to be able to run it locally.
 */

namespace Drupal\Tests\bynder\FunctionalJavascript;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Test the Bynder upload widget.
 *
 * @group bynder
 */
class BynderUploadWidgetTest extends JavascriptTestBase {

  use TestFileCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'ctools',
    'entity_browser_bynder_test',
    'bynder',
    'bynder_test_module',
    'node',
    'file',
    'image',
    'system',
    'dblog',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'media_entity_ct', 'name' => 'Media Type']);

    FieldStorageConfig::create([
      'field_name' => 'field_reference',
      'type' => 'entity_reference',
      'entity_type' => 'node',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'settings' => [
        'target_type' => 'media',
      ],
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_reference',
      'entity_type' => 'node',
      'bundle' => 'media_entity_ct',
      'label' => 'Reference',
      'settings' => [
        'handler' => 'default:media',
        'handler_settings' => [
          'target_bundles' => [
            'bynder' => 'bynder',
          ],
        ],
      ],
    ])->save();

    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display */
    $form_display = $this->container->get('entity_type.manager')
      ->getStorage('entity_form_display')
      ->load('node.media_entity_ct.default');

    $form_display->setComponent('field_reference', [
      'type' => 'entity_browser_entity_reference',
      'settings' => [
        'entity_browser' => 'bynder',
        'open' => TRUE,
      ],
    ])->save();

    $account = $this->drupalCreateUser([
      'administer entity browsers',
    ]);
    $this->drupalLogin($account);
  }

  /**
   * Tests the Bynder upload widget.
   */
//  public function testBynderUploadWidget() {
//    $form_state_value = [
//      'triggering_element' => [
//        '#eb_widget_main_submit' => TRUE,
//      ],
//      'values' => [
//        'upload' => [
//          'uploaded_files' => [
//            0 => [
//              'path' => $this->container->get('file_system')->realpath($image_1->uri),
//              'filename' => $image_1->filename,
//            ],
//          ],
//        ],
//      ],
//      'entity_browser' => [
//        'selected_entities' => [],
//      ],
//      'input' => [],
//      'uploaded_entities' => [$media],
//    ];
//    $form_state->setFormState($form_state_value);
//    $element = [
//      'upload' => [
//        'uploaded_files' => [
//          '#parents' => [''],
//        ],
//      ],
//    ];
//    $form = [];
//
//    $no_media = \Drupal::entityTypeManager()->getStorage('media')
//      ->loadByProperties(['field_media_uuid' => '123']);
//    $this->assertEmpty($no_media);
//
//    $widget->submit($element, $form, $form_state);
//
//    $media = \Drupal::entityTypeManager()->getStorage('media')
//      ->loadByProperties(['field_media_uuid' => '123']);
//
//    $this->assertNotEmpty($media);
//
//    $this->drupalGet('admin/structure/media/manage/media_type');
//    $this->getSession()->getPage()->selectFieldOption('type', 'generic');
//    $this->assertSession()->assertWaitOnAjaxRequest();
//    $this->getSession()->getPage()->pressButton('Save media bundle');
//
//    $form_state_value['triggering_element']['#bynder_upload_submit'] = TRUE;
//    $form_state->setFormState($form_state_value);
//
//    $widget->submit($element, $form, $form_state);
//
//    $this->drupalGet('admin/reports/dblog');
//    $this->assertSession()->responseContains('Media bundle Media type is not using Bynder plugin. Please fix the Bynder search widget configuration.');
//
//    $this->drupalGet('admin/reports/dblog/confirm');
//    $this->getSession()->getPage()->pressButton('Confirm');
//    $this->assertSession()->responseNotContains('Media bundle Media type is not using Bynder plugin. Please fix the Bynder search widget configuration.');
//
//    current($media)->delete();
//    $this->drupalGet('admin/structure/media/manage/media_type');
//    $this->getSession()->getPage()->clickLink('Delete');
//    $this->getSession()->getPage()->pressButton('Delete');
//
//    $widget->submit($element, $form, $form_state);
//    $this->drupalGet('admin/reports/dblog');
//    $this->assertSession()->responseContains('Media bundle media_type does not exists. Please fix the Bynder search widget configuration.');
//  }

  /**
   * Tests upload configuration form.
   *
   * @return void
   */
  public function testUploadConfigurationForm() {
    $metaproperties = [
      'test_filter' => [
        'label' => 'Test filter',
        'name' => 'test_filter',
        'id' => 'test_filter',
        'isFilterable' => TRUE,
        'isMultiselect' => FALSE,
        'isRequired' => FALSE,
        'zindex' => 1,
        'options' => [
          'optiona1' => [
            'displayLabel' => 'option A1',
            'id' => 'optiona1',
          ],
          'optiona2' => [
            'displayLabel' => 'option A2',
            'id' => 'optiona2',
          ],
        ],
      ],
      'test_another_filter' => [
        'label' => 'Test another filter',
        'name' => 'test_another_filter',
        'id' => 'test_another_filter',
        'isFilterable' => TRUE,
        'isMultiselect' => FALSE,
        'isRequired' => FALSE,
        'zindex' => 1,
        'options' => [
          'optionb1' => [
            'displayLabel' => 'option B1',
            'id' => 'optionb1',
          ],
          'optionb2' => [
            'displayLabel' => 'option B2',
            'id' => 'optionb2',
          ],
        ],
      ],
    ];
    \Drupal::state()->set('bynder.bynder_test_metaproperties', $metaproperties);

    \Drupal::state()->set('bynder.bynder_test_brands', [
      [
        'id' => 'brand_id',
        'name' => 'Brand Name',
        'subBrands' => [],
      ],
    ]);

    $this->drupalGet('admin/config/content/entity_browser/bynder/widgets');

    // Confirm no upload widget is present, remove search widget.
    $this->assertSession()->pageTextNotContains('Label (Bynder upload)');
    $this->assertSession()->fieldValueEquals('Label (Bynder search)', 'Bynder');
    $this->getSession()->getPage()->pressButton('Delete');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextNotContains('Label (Bynder search)');

    // Add upload widget and confirm default values.
    $this->getSession()->getPage()->selectFieldOption('widget', 'bynder_upload');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->fieldValueEquals('Label (Bynder upload)', 'bynder_upload');
    $this->assertSession()->fieldValueEquals('Submit button text', 'Select entities');
    $this->assertSession()->fieldValueEquals('Allowed file extensions', 'jpg jpeg png gif');
    $this->assertSession()->fieldValueEquals('Dropzone drag-n-drop zone text', 'Drop files here to upload them.');
    $this->assertSession()->fieldValueEquals('Tags', '');

    // Set custom values, save and check if they were saved.
    $this->getSession()->getPage()->fillField('Label (Bynder upload)', 'Upload');
    $this->getSession()->getPage()->fillField('Submit button text', 'Upload assets');
    $this->getSession()->getPage()->fillField('Allowed file extensions', 'png jpg');
    $this->getSession()->getPage()->fillField('Dropzone drag-n-drop zone text', 'Drop files...');
    $this->getSession()->getPage()->fillField('Tags', 'foo,bar,     baz');
    $this->getSession()->getPage()->selectFieldOption('Media type', 'Bynder');
    $this->getSession()->getPage()->selectFieldOption('Brand', 'Brand Name');
    $this->getSession()->getPage()->pressButton('Finish');

    $entity_browser = \Drupal\entity_browser\Entity\EntityBrowser::load('bynder');
    $this->assertTrue($entity_browser->getWidgets()->count() == 1);
    $widget_uuid = $entity_browser->getWidgets()->getInstanceIds();
    $widget_uuid = reset($widget_uuid);
    $upload_widget = $entity_browser->getWidget($widget_uuid);
    $expected_config = [
      'settings' => [
        'brand' => 'brand_id',
        'extensions' => 'png jpg',
        'dropzone_description' => 'Drop files...',
        'tags' => ['foo', 'bar', 'baz'],
        'media_type' => 'bynder',
        'submit_text' => 'Upload assets',
        'metaproperty_options' => [],
      ],
      'uuid' => $widget_uuid,
      'weight' => 1,
      'label' => 'Upload',
      'id' => 'bynder_upload',
    ];
    $this->assertEquals($expected_config, $upload_widget->getConfiguration());

    $this->drupalGet('admin/config/content/entity_browser/bynder/widgets');
    $this->assertSession()->fieldValueEquals('Label (Bynder upload)', 'Upload');
    $this->assertSession()->fieldValueEquals('Submit button text', 'Upload assets');
    $this->assertTrue($this->xpath('//select[@name="table[' . $widget_uuid . '][form][media_type]"]//option[@selected="selected" and @value="bynder"]'));
    $this->assertTrue($this->xpath('//select[@name="table[' . $widget_uuid . '][form][brand]"]//option[@selected="selected" and @value="brand_id"]'));
    $this->assertSession()->fieldValueEquals('Allowed file extensions', 'png jpg');
    $this->assertSession()->fieldValueEquals('Dropzone drag-n-drop zone text', 'Drop files...');
    $this->assertSession()->fieldValueEquals('Tags', 'foo, bar, baz');

    // Play with metaproperties AJAX form and confirm it saves correctly.
    $this->getSession()->getPage()->checkField('table[' . $widget_uuid . '][form][metaproperties][test_filter]');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->selectFieldOption('table[' . $widget_uuid . '][form][metaproperty_options][test_filter][]', 'optiona2');
    $this->getSession()->getPage()->pressButton('Finish');

    $entity_browser = \Drupal\entity_browser\Entity\EntityBrowser::load('bynder');
    $this->assertTrue($entity_browser->getWidgets()->count() == 1);
    $widget_uuid = $entity_browser->getWidgets()->getInstanceIds();
    $widget_uuid = reset($widget_uuid);
    $upload_widget = $entity_browser->getWidget($widget_uuid);
    $expected_config = [
      'settings' => [
        'brand' => 'brand_id',
        'extensions' => 'png jpg',
        'dropzone_description' => 'Drop files...',
        'tags' => ['foo', 'bar', 'baz'],
        'media_type' => 'bynder',
        'submit_text' => 'Upload assets',
        'metaproperty_options' => [
          'test_filter' => ['optiona2'],
        ],
      ],
      'uuid' => $widget_uuid,
      'weight' => 1,
      'label' => 'Upload',
      'id' => 'bynder_upload',
    ];
    $this->assertEquals($expected_config, $upload_widget->getConfiguration());
  }

}
