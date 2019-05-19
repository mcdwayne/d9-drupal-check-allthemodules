<?php

namespace Drupal\Tests\textimage\Functional;

use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\node\Entity\Node;
use Drupal\Tests\image\Kernel\ImageFieldCreationTrait;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Test integration of Textimage with the Redirect module.
 *
 * @group Textimage
 */
class TextimageRedirectIntegrationTest extends TextimageTestBase {

  use ImageFieldCreationTrait;
  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'textimage',
    'node',
    'image_effects',
    'redirect',
  ];

  /**
   * Test integration of Textimage with the Redirect module.
   */
  public function testTextimageWithRedirectInstalled() {
    // Create an image field for testing.
    $field_name = strtolower($this->randomMachineName());
    $min_resolution = 50;
    $max_resolution = 100;
    $field_settings = [
      'max_resolution' => $max_resolution . 'x' . $max_resolution,
      'min_resolution' => $min_resolution . 'x' . $min_resolution,
      'alt_field' => 1,
    ];
    $this->createImageField($field_name, 'article', [], $field_settings);

    // Create a new node.
    // Get image 'image-1.png'.
    $field_value = $this->getTestFiles('image', 39325)[0];
    $nid = $this->createTextimageNode('image', $field_name, $field_value, 'article', $this->randomMachineName());
    $node = Node::load($nid);
    $node_title = $node->get('title')[0]->get('value')->getValue();

    // Get the stored image.
    $fid = $node->{$field_name}[0]->get('target_id')->getValue();
    $source_image_file = File::load($fid);
    $source_image_file_url = file_create_url($source_image_file->getFileUri());

    // Get Textimage URL.
    $textimage = $this->textimageFactory->get()
      ->setSourceImageFile($source_image_file)
      ->setStyle(ImageStyle::load('textimage_test'))
      ->setTokenData(['node' => $node, 'file' => $source_image_file])
      ->process(NULL);
    $rel_url = file_url_transform_relative($textimage->getUrl()->toString());
    $this->assertFileNotExists($textimage->getUri());

    // Test the textimage formatter - no link.
    $display = $this->entityDisplayRepository->getViewDisplay('node', $node->getType(), 'default');
    $display_options['type'] = 'textimage_image_field_formatter';
    $display_options['settings']['image_style'] = 'textimage_test';
    $display_options['settings']['image_link'] = '';
    $display_options['settings']['image_alt'] = 'Alternate text: [node:title]';
    $display_options['settings']['image_title'] = 'Title: [node:title]';
    $display->setComponent($field_name, $display_options)
      ->save();
    $this->drupalGet('node/' . $nid);
    $elements = $this->cssSelect("img[src='$rel_url']");
    $this->assertNotEmpty($elements);
    $this->assertSame($elements[0]->getAttribute('alt'), 'Alternate text: ' . $node_title);
    $this->assertSame($elements[0]->getAttribute('title'), 'Title: ' . $node_title);

    // Get the file via URL so that it gets created on the file system.
    $this->drupalGet($rel_url);
    $this->assertFileExists($textimage->getUri());
  }

}
