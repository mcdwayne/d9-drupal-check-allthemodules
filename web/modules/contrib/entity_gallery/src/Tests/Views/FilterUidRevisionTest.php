<?php

namespace Drupal\entity_gallery\Tests\Views;

use Drupal\views\Views;

/**
 * Tests the entity_gallery_uid_revision handler.
 *
 * @group entity_gallery
 */
class FilterUidRevisionTest extends EntityGalleryTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_filter_entity_gallery_uid_revision');

  /**
   * Tests the entity_gallery_uid_revision filter.
   */
  public function testFilter() {
    $author = $this->drupalCreateUser();
    $no_author = $this->drupalCreateUser();

    $expected_result = array();
    // Create one entity gallery, with the author as the entity gallery author.
    $entity_gallery = $this->drupalCreateEntityGallery(array('uid' => $author->id()));
    $expected_result[] = array('egid' => $entity_gallery->id());
    // Create one entity gallery of which an additional revision author will be
    // the author.
    $entity_gallery = $this->drupalCreateEntityGallery(array('revision_uid' => $no_author->id()));
    $expected_result[] = array('egid' => $entity_gallery->id());
    $revision = clone $entity_gallery;
    // Force to add a new revision.
    $revision->set('vid', NULL);
    $revision->set('revision_uid', $author->id());
    $revision->save();

    // Create oneen tity gallery on which the author has neither authorship of
    // revisions  or the main entity gallery.
    $this->drupalCreateEntityGallery(array('uid' => $no_author->id()));

    $view = Views::getView('test_filter_entity_gallery_uid_revision');
    $view->initHandlers();
    $view->filter['uid_revision']->value = array($author->id());

    $this->executeView($view);
    $this->assertIdenticalResultset($view, $expected_result, array('egid' => 'egid'), 'Make sure that the view only returns entity galleries which match either the entity gallery or the revision author.');
  }

}
