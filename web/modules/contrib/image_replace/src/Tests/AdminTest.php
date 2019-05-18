<?php
/**
 * @file
 * Contains Drupal\image_replace\Tests\AdminTest.
 */

namespace Drupal\image_replace\Tests;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\image\Entity\ImageStyle;
use Drupal\node\Entity\Node;

/**
 * Tests the administrative interface of the image replace module.
 *
 * @group image_replace
 */
class AdminTest extends ImageReplaceTestBase {

  public static $modules = array('views', 'node', 'field_ui', 'image_replace');

  protected $styleName;

  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create an image style containing the replace effect.
    $this->styleName = 'image_replace_test';
    $this->createImageStyle($this->styleName);

    // Add the replacement image field to the article bundle.
    $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));
    $this->createImageField('image_replacement', 'article');

    // Add the original image field to the article bundle and specify
    // the replacement image as replacement.
    $this->createImageField('image_original', 'article');

    $display = entity_get_display('node', 'article', 'teaser');
    $display_options = $display->getComponent('image_original');
    $display_options['type'] = 'image';
    $display_options['settings']['image_style'] = $this->styleName;
    $display->set('status', TRUE);
    $display->setComponent('image_original', $display_options);
    $display->save();

    $display = entity_get_display('node', 'article', 'full');
    $display_options = $display->getComponent('image_original');
    $display_options['type'] = 'image';
    $display_options['settings']['image_style'] = NULL;
    $display->set('status', TRUE);
    $display->setComponent('image_original', $display_options);
    $display->save();

    $this->adminUser = $this->drupalCreateUser(array(
      'access content',
      'administer content types',
      'administer image styles',
      'administer node fields',
      'administer nodes',
      'create article content',
      'delete any article content',
      'edit any article content',
    ));
  }

  /**
   * Tests image replacement on node entities.
   */
  public function testFieldEditUi() {
    list($original_file, $replacement_file) = $this->createTestFiles();

    // Create an unrelated image style.
    $unrelated_style_name = 'other_style';
    $style = ImageStyle::create([
      'name' => $unrelated_style_name,
      'label' => $this->randomString(),
    ]);
    $style->save();

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/structure/types/manage/article/fields/node.article.image_original');
    $this->assertResponse(200);

    // Verify that a select field is present with a list of available source
    // fields for the generated image style.
    $field_name = 'third_party_settings[image_replace][image_style_map][' . $this->styleName . '][source_field]';
    $this->assertFieldByName($field_name, "0", 'Image replace selector found for style containing the image replace effect.');
    $result = $this->xpath($this->constructFieldXpath('name', $field_name));

    $options = $this->getAllOptions($result[0]);
    $contains_image_original = FALSE;
    $contains_image_replacement = FALSE;
    foreach ($options as $option) {
      $contains_image_original |= $option['value'] == 'image_original';
      $contains_image_replacement |= $option['value'] == 'image_replacement';
    }
    $this->assertFalse($contains_image_original, 'Original image field is not in the list of options.');
    $this->assertTrue($contains_image_replacement, 'Replacement image field is in the list of options.');

    // Verify that no select field is present for an image style which does not
    // contain the replacement effect.
    $field_name = 'third_party_settings[image_replace][image_style_map][' . $unrelated_style_name . '][source_field]';
    $this->assertNoFieldByName($field_name, NULL, 'Image replace settings not present for unrelated style.');

    // Choose the replacement image field as the replacement source.
    $field_name = 'third_party_settings[image_replace][image_style_map][' . $this->styleName . '][source_field]';
    $edit = array(
      $field_name => 'image_replacement',
    );
    $this->drupalPostForm(NULL, $edit, t('Save settings'));
    $this->assertResponse(200);

    // Verify that no message is displayed if the mapping changes when there is
    // no existing content.
    $this->assertNoText('The image replacement settings have been modified. As a result, it is necessary to rebuild the image replacement mapping for existing content. Note: The replacement mapping is updated automatically when saving an entity.');

    // Post new content.
    $edit = array(
      'title[0][value]' => $this->randomString(),
      'promote[value]' => 1,
    );
    $edit['files[image_original_0]'] = drupal_realpath($original_file->getFileUri());
    $edit['files[image_replacement_0]'] = drupal_realpath($replacement_file->getFileUri());
    $this->drupalPostForm('node/add/article', $edit, $this->getNodeSaveButtonText());
    $this->assertResponse(200);

    $edit = array(
      'image_original[0][alt]' => $this->randomString(),
      'image_replacement[0][alt]' => $this->randomString(),
    );
    $this->drupalPostForm(NULL, $edit, $this->getNodeSaveButtonText());
    $this->assertResponse(200);

    preg_match('/node\/([0-9]+)/', $this->getUrl(), $matches);
    $node = Node::load($matches[1]);

    // Verify that the original image is shown on the full node view.
    $generated_url = file_create_url($node->image_original->entity->getFileUri());
    $relative_url = file_url_transform_relative($generated_url);
    $this->assertRaw(SafeMarkup::checkPlain($relative_url), 'Original image displayed');

    $generated_image_data = $this->drupalGet($generated_url);
    $this->assertResponse(200);

    // Assert that the result is the original image.
    $generated_uri = file_unmanaged_save_data($generated_image_data);
    $this->assertTrue($this->imageIsOriginal($generated_uri), 'The generated file should be the same as the original file on full node view.');

    // Verify that the replacement image is shown on the teaser.
    $this->drupalGet('node');
    $this->assertResponse(200);
    $generated_url = ImageStyle::load($this->styleName)->buildUrl($node->image_original->entity->getFileUri());
    $relative_url = file_url_transform_relative($generated_url);
    $this->assertRaw(SafeMarkup::checkPlain($relative_url), SafeMarkup::format('Image displayed using style @style.', array('@style' => $this->styleName)));

    $generated_image_data = $this->drupalGet($generated_url);
    $this->assertResponse(200);

    // Assert that the result is the replacement image.
    $generated_uri = file_unmanaged_save_data($generated_image_data);
    $this->assertTrue($this->imageIsReplacement($generated_uri), 'The generated file should be the same as the replacement file on teaser.');

    // Go back to the field settings and reset the replacement mapping.
    $field_name = 'third_party_settings[image_replace][image_style_map][' . $this->styleName . '][source_field]';
    $edit = array(
      $field_name => '0',
    );
    $this->drupalPostForm('admin/structure/types/manage/article/fields/node.article.image_original', $edit, t('Save settings'));
    $this->assertResponse(200);

    // Verify that a message is displayed if the mapping changes when there is
    // existing content.
    $this->assertText('The image replacement settings have been modified. As a result, it is necessary to rebuild the image replacement mapping for existing content. Note: The replacement mapping is updated automatically when saving an entity.');
  }

  /**
   * Returns value string for node form submit button (changed in 8.4.x).
   */
  protected function getNodeSaveButtonText() {
    list($core_major, $core_minor) = explode('.', \Drupal::VERSION, 2);

    if ($core_major == 8 && $core_minor < 4) {
      return t('Save and publish');
    }
    else {
      return t('Save');
    }
  }

}
