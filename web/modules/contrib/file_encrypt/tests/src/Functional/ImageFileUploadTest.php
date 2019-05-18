<?php

namespace Drupal\Tests\file_encrypt\Functional;

use Drupal\Component\Utility\UrlHelper;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests uploading images as well as viewing files on the rendered entity.
 *
 * @group file_encrypt
 */
class ImageFileUploadTest extends FunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    NodeType::create([
      'type' => 'page',
    ])->save();

    FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_test_image',
      'type' => 'image',
      'settings' => [
        'uri_scheme' => 'encrypt',
      ],
      'third_party_settings' => [
        'file_encrypt' => [
          'encryption_profile' => 'encryption_profile_1',
        ],
      ],
    ])->save();

    FieldConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_test_image',
      'bundle' => 'page',
      'settings' => [
        'file_directory' => 'encryption_profile_1',
        'file_extensions' => 'png',
      ],
    ])->save();

    $this->drupalGetTestFiles('text');

    $form_display = entity_get_form_display('node', 'page', 'default');
    $form_display->setComponent('field_test_image', [
      'type' => 'image_image',
    ]);
    $form_display->save();

    $style = ImageStyle::create([
      'name' => 'test',
      'label' => 'Test',
      'effects' => [
      ],
    ]);
    $effect = [
      'id' => 'image_crop',
      'data' => [
        'width' => 200,
        'height' => 200,
      ],
      'weight' => 0,
    ];
    $style->addImageEffect($effect);
    $style->save();

    $view_display = entity_get_display('node', 'page', 'default');
    $view_display->setComponent('field_test_image', [
      'type' => 'image',
      'settings' => [
        'image_style' => 'test',
      ],
    ]);
    $view_display->save();
  }

  /**
   * Tests uploading an actual file.
   */
  public function testImageUpload() {
    $account = $this->drupalCreateUser(['create page content']);
    $this->drupalLogin($account);

    $text_files = $this->drupalGetTestFiles('image');
    $image_file = File::create((array) current($text_files));
    $image_file->getFileUri();

    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');
    $assert->statusCodeEquals(200);
    $edit = [
      'title[0][value]' => 'Test title',
      'files[field_test_image_0]' => drupal_realpath($image_file->getFileUri()),
    ];
    $this->submitForm($edit, 'Save');
    $this->submitForm([
      'field_test_image[0][alt]' => 'Alternative text',
    ], 'Save');

    // Ensure the file was saved.
    $nodes = Node::loadMultiple();
    $this->assertCount(1, $nodes);
    $last_node = end($nodes);
    $this->assertEquals('encrypt://encryption_profile_1/image-test_0.png', $last_node->field_test_image->entity->getFileUri());

    // Ensure the file was visible.
    $node = $this->getSession()->getPage()->find('css', 'img');
    $this->assertNotNull($node);
    $image_src = $node->getAttribute('src');
    $this->assertContains('/encrypt/files/styles/test/encrypt/encryption_profile_1/image-test_0.png', $image_src);

    // Ensure the image is actually 200x200 pixes wide 
    $parse_result = UrlHelper::parse($image_src);
    $this->drupalGet($parse_result['path'], ['query' => $parse_result['query']]);
    $this->assertSession()->statusCodeEquals(200);

    $this->assertEquals([
      200,
      200,
      3,
      'width="200" height="200"',
      'bits' => 8,
      'mime' => 'image/png',
    ], getimagesize('/tmp/foo.png'));
  }

}
