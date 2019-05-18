<?php

namespace Drupal\Tests\imagefield_tokens\Functional;

use Drupal\Core\Url;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;
use Drupal\Tests\image\Functional\ImageFieldTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests that ImageFieldTokens widget and formatter works correctly.
 *
 * @group image
 */
class ImageFieldTokensFormatterTest extends ImageFieldTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['field_ui', 'image', 'token', 'imagefield_tokens'];

  use AssertPageCacheContextsAndTagsTrait;
  use ImageFieldTokensTestingTrait;
  use TestFileCreationTrait {
    getTestFiles as drupalGetTestFiles;
    compareFiles as drupalCompareFiles;
  }

  /**
   * Set to TRUE to strict check all configuration saved.
   *
   * @var bool
   *
   * @see \Drupal\Core\Config\Development\ConfigSchemaChecker
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Test image formatters on node display.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testImageFieldFormatters() {
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $field_name = strtolower($this->randomMachineName());
    $field_settings = ['alt_field_required' => 0];
    $instance = $this->createImageFieldTokensField($field_name, 'article', ['uri_scheme' => 'public'], $field_settings);

    // Go to manage display page.
    $this->drupalGet('admin/structure/types/manage/article/display');

    // Test for existence of link to image styles configuration.
    $this->drupalPostForm(NULL, [], "{$field_name}_settings_edit");
    $this->assertSession()->linkByHrefExists(Url::fromRoute('entity.image_style.collection')->toString(), 0, 'Link to image styles configuration is found');

    // Remove 'administer image styles' permission from testing admin user.
    $admin_user_roles = $this->adminUser->getRoles(TRUE);
    user_role_change_permissions(reset($admin_user_roles), ['administer image styles' => FALSE]);

    // Go to manage display page again.
    $this->drupalGet('admin/structure/types/manage/article/display');

    // Test for absence of link to image styles configuration.
    $this->drupalPostForm(NULL, [], "{$field_name}_settings_edit");
    $this->assertSession()->linkByHrefNotExists(Url::fromRoute('entity.image_style.collection')->toString(), 'Link to image styles configuration is absent when permissions are insufficient');

    // Restore 'administer image styles' permission to testing admin user.
    user_role_change_permissions(reset($admin_user_roles), ['administer image styles' => TRUE]);

    // Create a new node with an image attached.
    $test_image = current($this->drupalGetTestFiles('image'));

    // Ensure that preview works.
    $this->previewNodeImage($test_image, $field_name, 'article');

    // After previewing, make the alt field required. It cannot be required
    // during preview because the form validation will fail.
    /* @var \Drupal\field\Entity\FieldConfig $instance */
    $instance->setSetting('alt_field_required', 1);
    $instance->save();

    // Create alt text for the image.
    $alt = '[node:title]';

    // Save node.
    $nid = $this->uploadNodeImage($test_image, $field_name, 'article', $alt);
    /* @var \Drupal\node\NodeStorage $node_storage */
    $node_storage->resetCache([$nid]);
    $node = $node_storage->load($nid);

    // Preparing image field formatter display settings.
    $display_options = [
      'type' => 'imagefield_tokens',
      'settings' => ['image_link' => 'content'],
    ];

    // Receiving response array from the formatter.
    $formatter_response_result = $node->{$field_name}->view($display_options);

    // Verify that the image has converted 'Alt' value for token [node:title].
    self::assertEquals($formatter_response_result[0]['#item']->alt, $node->getTitle(), 'Make sure ALT field has been processed correctly!');
  }

}
