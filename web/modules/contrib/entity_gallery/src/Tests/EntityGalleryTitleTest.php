<?php

namespace Drupal\entity_gallery\Tests;

use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Component\Utility\Html;

/**
 * Tests entity gallery title.
 *
 * @group entity_gallery
 */
class EntityGalleryTitleTest extends EntityGalleryTestBase {

  use CommentTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('comment', 'views', 'block');

  /**
   * A user with permission to bypass access content.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('system_breadcrumb_block');

    $this->adminUser = $this->drupalCreateUser(array('administer entity galleries', 'create article entity galleries', 'create page entity galleries', 'post comments'));
    $this->drupalLogin($this->adminUser);
    $this->addDefaultCommentField('entity_gallery', 'page');
  }

  /**
   *  Creates one entity gallery and tests if the entity gallery title has the correct value.
   */
  function testEntityGalleryTitle() {
    // Create "Basic page" content with title.
    // Add the entity gallery to the frontpage so we can test if teaser links
    // are clickable.
    $settings = array(
      'title' => $this->randomMachineName(8),
      'promote' => 1,
    );
    $entity_gallery = $this->drupalCreateEntityGallery($settings);

    // Test <title> tag.
    $this->drupalGet('gallery/' . $entity_gallery->id());
    $xpath = '//title';
    $this->assertEqual(current($this->xpath($xpath)), $entity_gallery->label() .' | Drupal', 'Page title is equal to entity gallery title.', 'Entity gallery');

    // Test breadcrumb in comment preview.
    $this->drupalGet('comment/reply/entity_gallery/' . $entity_gallery->id() . '/comment');
    $xpath = '//nav[@class="breadcrumb"]/ol/li[last()]/a';
    $this->assertEqual(current($this->xpath($xpath)), $entity_gallery->label(), 'Entity gallery breadcrumb is equal to entity gallery title.', 'Entity gallery');

    // Test entity gallery title in comment preview.
    $this->assertEqual(current($this->xpath('//article/h2/a/span')), $entity_gallery->label(), 'Entity gallery preview title is equal to entity gallery title.', 'Entity gallery');

    // Test edge case where entity gallery title is set to 0.
    $settings = array(
      'title' => 0,
    );
    $entity_gallery = $this->drupalCreateEntityGallery($settings);
    // Test that 0 appears as <title>.
    $this->drupalGet('gallery/' . $entity_gallery->id());
    $this->assertTitle(0 . ' | Drupal', 'Page title is equal to 0.', 'Entity gallery');
    // Test that 0 appears in the template <h1>.
    $xpath = '//h1';
    $this->assertEqual(current($this->xpath($xpath)), 0, 'Entity gallery title is displayed as 0.', 'Entity gallery');

    // Test edge case where entity gallery title contains special characters.
    $edge_case_title = 'article\'s "title".';
    $settings = array(
      'title' => $edge_case_title,
    );
    $entity_gallery = $this->drupalCreateEntityGallery($settings);
    // Test that the title appears as <title>. The title will be escaped on the
    // the page.
    $edge_case_title_escaped = Html::escape($edge_case_title);
    $this->drupalGet('gallery/' . $entity_gallery->id());
    $this->assertTitle($edge_case_title_escaped . ' | Drupal', 'Page title is equal to article\'s "title".', 'Entity gallery');

    // Test that the title appears as <title> when reloading the entity gallery
    // page.
    $this->drupalGet('gallery/' . $entity_gallery->id());
    $this->assertTitle($edge_case_title_escaped . ' | Drupal', 'Page title is equal to article\'s "title".', 'Entity gallery');

  }
}
