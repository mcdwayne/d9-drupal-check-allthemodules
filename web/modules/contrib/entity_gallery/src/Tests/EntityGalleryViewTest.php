<?php

namespace Drupal\entity_gallery\Tests;

/**
 * Tests the gallery/{entity_gallery} page.
 *
 * @group entity_gallery
 * @see \Drupal\entity_gallery\Controller\EntityGalleryController
 */
class EntityGalleryViewTest extends EntityGalleryTestBase {

  /**
   * Tests the html head links.
   */
  public function testHtmlHeadLinks() {
    $entity_gallery = $this->drupalCreateEntityGallery();

    $this->drupalGet($entity_gallery->urlInfo());

    $result = $this->xpath('//link[@rel = "version-history"]');
    $this->assertEqual($result[0]['href'], $entity_gallery->url('version-history'));

    $result = $this->xpath('//link[@rel = "edit-form"]');
    $this->assertEqual($result[0]['href'], $entity_gallery->url('edit-form'));

    $result = $this->xpath('//link[@rel = "canonical"]');
    $this->assertEqual($result[0]['href'], $entity_gallery->url());
  }

  /**
   * Tests that we store and retrieve multi-byte UTF-8 characters correctly.
   */
  public function testMultiByteUtf8() {
    $title = '🐝';
    $this->assertTrue(mb_strlen($title, 'utf-8') < strlen($title), 'Title has multi-byte characters.');
    $entity_gallery = $this->drupalCreateEntityGallery(array('title' => $title));
    $this->drupalGet($entity_gallery->urlInfo());
    $result = $this->xpath('//h1[contains(@class, "page-title")]/span');
    $this->assertEqual((string) $result[0], $title, 'The passed title was returned.');
  }

}
