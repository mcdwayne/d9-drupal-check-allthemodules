<?php
/**
 * @file
 * Contains Drupal\image_replace\Tests\EntityTest.
 */

namespace Drupal\image_replace\Tests;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\image\Entity\ImageStyle;
use Drupal\node\Entity\Node;

/**
 * Tests core entity API integration for the replace image effect.
 *
 * @group image_replace
 */
class EntityTest extends ImageReplaceTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('views', 'node', 'image_replace');

  protected $styleName;

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
    $field = $this->createImageField('image_original', 'article');
    $field->setThirdPartySetting('image_replace', 'image_style_map', array(
      $this->styleName => array(
        'source_field' => 'image_replacement',
      ),
    ));
    $field->save();

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
  }

  /**
   * Tests image replacement on node entities.
   */
  public function testNodeView() {
    list($original_file, $replacement_file) = $this->createTestFiles();

    $node = Node::create([
      'type' => 'article',
      'title' => $this->randomString(16),
      'promote' => 1,
    ]);

    $node->image_original->target_id = $original_file->id();
    $node->image_original->alt = $alt = $this->randomMachineName();
    $node->image_original->title = $title = $this->randomMachineName();
    $node->image_replacement->target_id = $replacement_file->id();
    $node->image_replacement->alt = $alt = $this->randomMachineName();
    $node->image_replacement->title = $title = $this->randomMachineName();
    $node->save();

    // Check teaser.
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

    // Check full view.
    $this->drupalGet('node/' . $node->id());
    $this->assertResponse(200);
    $generated_url = file_create_url($node->image_original->entity->getFileUri());
    $relative_url = file_url_transform_relative($generated_url);
    $this->assertRaw(SafeMarkup::checkPlain($relative_url), 'Original image displayed');

    $generated_image_data = $this->drupalGet($generated_url);
    $this->assertResponse(200);

    // Assert that the result is the original image.
    $generated_uri = file_unmanaged_save_data($generated_image_data);
    $this->assertTrue($this->imageIsOriginal($generated_uri), 'The generated file should be the same as the original file on full node view.');
  }

}
