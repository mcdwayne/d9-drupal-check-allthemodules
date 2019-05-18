<?php

namespace Drupal\Tests\file_encrypt\Functional;

use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests uploading files as well as viewing files on the rendered entity.
 *
 * @group file_encrypt
 */
class FileUploadTest extends FunctionalTestBase {

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
      'field_name' => 'field_test_file',
      'type' => 'file',
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
      'field_name' => 'field_test_file',
      'bundle' => 'page',
      'settings' => [
        'file_directory' => 'encryption_profile_1',
        'file_extensions' => 'txt',
      ],
    ])->save();

    $this->drupalGetTestFiles('text');

    $form_display = entity_get_form_display('node', 'page', 'default');
    $form_display->setComponent('field_test_file', [
      'type' => 'file_generic',
    ]);
    $form_display->save();

    $view_display = entity_get_display('node', 'page', 'default');
    $view_display->setComponent('field_test_file', [
      'type' => 'file_url_plain',
    ]);
    $view_display->save();
  }

  /**
   * Tests uploading an actual file.
   */
  public function testFileUpload() {
    $account = $this->drupalCreateUser(['create page content']);
    $this->drupalLogin($account);

    $text_files = $this->drupalGetTestFiles('text');
    $text_file = File::create((array) current($text_files));
    $text_file->getFileUri();

    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');
    $assert->statusCodeEquals(200);
    $edit = [
      'title[0][value]' => 'Test title',
      'files[field_test_file_0]' => drupal_realpath($text_file->getFileUri()),
    ];
    $this->submitForm($edit, 'Save');

    // Ensure the file was saved.
    $nodes = Node::loadMultiple();
    $this->assertCount(1, $nodes);
    $last_node = end($nodes);
    $this->assertEquals('encrypt://encryption_profile_1/text-0_0.txt', $last_node->field_test_file->entity->getFileUri());
  
    // Ensure the file was visible.
    $assert->pageTextContains('/encrypt/files?file=encryption_profile_1/text-0_0.txt');
  }

}
