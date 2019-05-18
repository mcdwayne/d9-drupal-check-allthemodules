<?php

namespace Drupal\Tests\imagefield_tokens\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\node\Entity\Node;
use Drupal\Tests\image\Functional\ImageFieldTestBase;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\file\Entity\File;

/**
 * Tests that ImageFieldTokens widget and formatter works correctly.
 *
 * @group image
 */
class ImageFieldTokensWidgetTest extends ImageFieldTestBase {

  /**
   * Set to TRUE to strict check all configuration saved.
   *
   * @var bool
   *
   * @see \Drupal\Core\Config\Development\ConfigSchemaChecker
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'image', 'token', 'imagefield_tokens'];

  use AssertPageCacheContextsAndTagsTrait;
  use ImageFieldTokensTestingTrait;
  use TestFileCreationTrait {
    getTestFiles as drupalGetTestFiles;
    compareFiles as drupalCompareFiles;
  }

  /**
   * Tests file widget element.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testWidgetElement() {
    // Check for image widget in add/node/article page.
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $field_name = strtolower($this->randomMachineName());
    $min_resolution = 50;
    $max_resolution = 100;
    $field_settings = [
      'max_resolution' => $max_resolution . 'x' . $max_resolution,
      'min_resolution' => $min_resolution . 'x' . $min_resolution,
      'alt_field' => 1,
    ];

    $this->createImageFieldTokensField($field_name, 'article', ['uri_scheme' => 'public'], $field_settings);
    $this->drupalGet('node/add/article');
    $this->assertNotEqual(0, count($this->xpath('//div[contains(@class, "field--widget-imagefield-tokens")]')), 'Image field widget found on add/node page', 'Browser');
    $this->assertNotEqual(0, count($this->xpath('//input[contains(@accept, "image/*")]')), 'Image field widget limits accepted files.', 'Browser');
    $this->assertSession()->pageTextNotContains('Image test on [site:name]');

    // Check for allowed image file extensions - default.
    $this->assertSession()->pageTextContains('Allowed types: png gif jpg jpeg.');

    // Try adding to the field config an unsupported extension, should not
    // appear in the allowed types.
    $field_config = FieldConfig::loadByName('node', 'article', $field_name);
    $field_config->setSetting('file_extensions', 'png gif jpg jpeg tiff')
      ->save();
    $this->drupalGet('node/add/article');
    $this->assertSession()->pageTextContains('Allowed types: png gif jpg jpeg.');

    // Add a supported extension and remove some supported ones, we should see
    // the intersect of those entered in field config with those supported.
    $field_config->setSetting('file_extensions', 'png jpe tiff')->save();
    $this->drupalGet('node/add/article');
    $this->assertSession()->pageTextContains('Allowed types: png jpe.');

    // Create a new node with an image attached.
    $test_image = current($this->drupalGetTestFiles('image'));

    // Ensure that preview works.
    $this->previewNodeImage($test_image, $field_name, 'article');

    // Create alt text for the image.
    $alt = '[node:title]';

    // Create and save node.
    Node::create([
      'title' => $this->randomMachineName(),
      'type' => 'article',
    ])->save();

    /* @var \Drupal\node\NodeStorage $node_storage */
    // Reset node cache and load it.
    $nid = 1;
    $node_storage->resetCache([$nid]);
    $node = $node_storage->load($nid);

    // Create a file entity from image_uri.
    $file = File::Create([
      'uri' => $test_image->uri,
    ]);
    $file->save();

    // Upload image to the entity.
    $node->{$field_name}->setValue([
      'target_id' => $file->id(),
      'alt' => $alt,
    ]);

    // Save node changes.
    $node->save();

    // Open node edit page and find 'Alt' field input.
    $this->drupalGet('node/1/edit');
    $path = "//input[@id='edit-" . $field_name . "-0-alt']";
    $xpath = $this->xpath($path);

    // Check 'Alt' field input for a valid data.
    self::assertEquals($xpath[0]->getValue(), $node->getTitle(), 'Make sure ALT field has been processed correctly!');
  }

}
