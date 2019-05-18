<?php

namespace Drupal\entity_gallery\Tests;

use Drupal\user\RoleInterface;

/**
 * Tests entity gallery administration page functionality.
 *
 * @group entity_gallery
 */
class EntityGalleryAdminTest extends EntityGalleryTestBase {
  /**
   * A user with permission to bypass entity gallery access.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * A user with the 'access entity gallery overview' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $baseUser1;

  /**
   * A normal user with permission to view own unpublished content.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $baseUser2;

  /**
   * A normal user with permission to bypass entity gallery access.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $baseUser3;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('views');

  protected function setUp() {
    parent::setUp();

    // Remove the "view own unpublished content" permission which is set
    // by default for authenticated users so we can test this permission
    // correctly.
    user_role_revoke_permissions(RoleInterface::AUTHENTICATED_ID, array('view own unpublished content'));

    $this->adminUser = $this->drupalCreateUser(array('access administration pages', 'access entity gallery overview', 'administer entity galleries', 'bypass entity gallery access'));
    $this->baseUser1 = $this->drupalCreateUser(['access entity gallery overview']);
    $this->baseUser2 = $this->drupalCreateUser(['access entity gallery overview', 'view own unpublished entity galleries']);
    $this->baseUser3 = $this->drupalCreateUser(['access entity gallery overview', 'bypass entity gallery access']);
  }

  /**
   * Tests that the table sorting works on the content admin pages.
   */
  function testContentAdminSort() {
    $this->drupalLogin($this->adminUser);

    $changed = REQUEST_TIME;
    foreach (array('dd', 'aa', 'DD', 'bb', 'cc', 'CC', 'AA', 'BB') as $prefix) {
      $changed += 1000;
      $entity_gallery = $this->drupalCreateEntityGallery(array('title' => $prefix . $this->randomMachineName(6)));
      db_update('entity_gallery_field_data')
        ->fields(array('changed' => $changed))
        ->condition('egid', $entity_gallery->id())
        ->execute();
    }

    // Test that the default sort by entity_gallery.changed DESC actually fires
    // properly.
    $entity_galleries_query = db_select('entity_gallery_field_data', 'eg')
      ->fields('eg', array('title'))
      ->orderBy('changed', 'DESC')
      ->execute()
      ->fetchCol();

    $this->drupalGet('admin/content/gallery');
    foreach ($entity_galleries_query as $delta => $string) {
      $elements = $this->xpath('//table[contains(@class, :class)]/tbody/tr[' . ($delta + 1) . ']/td[2]/a[normalize-space(text())=:label]', array(':class' => 'views-table', ':label' => $string));
      $this->assertTrue(!empty($elements), 'The entity gallery was found in the correct order.');
    }

    // Compare the rendered HTML entity gallery list to a query for the entity
    // galleries ordered by title to account for possible database-dependent
    // sort order.
    $entity_galleries_query = db_select('entity_gallery_field_data', 'eg')
      ->fields('eg', array('title'))
      ->orderBy('title')
      ->execute()
      ->fetchCol();

    $this->drupalGet('admin/content/gallery', array('query' => array('sort' => 'asc', 'order' => 'title')));
    foreach ($entity_galleries_query as $delta => $string) {
      $elements = $this->xpath('//table[contains(@class, :class)]/tbody/tr[' . ($delta + 1) . ']/td[2]/a[normalize-space(text())=:label]', array(':class' => 'views-table', ':label' => $string));
      $this->assertTrue(!empty($elements), 'The entity gallery was found in the correct order.');
    }
  }

  /**
   * Tests content overview with different user permissions.
   *
   * Taxonomy filters are tested separately.
   *
   * @see TaxonomyEntityGalleryFilterTestCase
   */
  function testContentAdminPages() {
    $this->drupalLogin($this->adminUser);

    // Use an explicit changed time to ensure the expected order in the content
    // admin listing. We want these to appear in the table in the same order as
    // they appear in the following code, and the 'content' View has a table
    // style configuration with a default sort on the 'changed' field DESC.
    $time = time();
    $entity_galleries['published_page'] = $this->drupalCreateEntityGallery(array('type' => 'page', 'changed' => $time--));
    $entity_galleries['published_article'] = $this->drupalCreateEntityGallery(array('type' => 'article', 'changed' => $time--));
    $entity_galleries['unpublished_page_1'] = $this->drupalCreateEntityGallery(array('type' => 'page', 'changed' => $time--, 'uid' => $this->baseUser1->id(), 'status' => 0));
    $entity_galleries['unpublished_page_2'] = $this->drupalCreateEntityGallery(array('type' => 'page', 'changed' => $time, 'uid' => $this->baseUser2->id(), 'status' => 0));

    // Verify view, edit, and delete links for any content.
    $this->drupalGet('admin/content/gallery');
    $this->assertResponse(200);

    $entity_gallery_type_labels = $this->xpath('//td[contains(@class, "views-field-type")]');
    $delta = 0;
    foreach ($entity_galleries as $entity_gallery) {
      $this->assertLinkByHref('gallery/' . $entity_gallery->id());
      $this->assertLinkByHref('gallery/' . $entity_gallery->id() . '/edit');
      $this->assertLinkByHref('gallery/' . $entity_gallery->id() . '/delete');
      // Verify that we can see the content type label.
      $this->assertEqual(trim((string) $entity_gallery_type_labels[$delta]), $entity_gallery->type->entity->label());
      $delta++;
    }

    // Verify filtering by publishing status.
    $this->drupalGet('admin/content/gallery', array('query' => array('status' => TRUE)));

    $this->assertLinkByHref('gallery/' . $entity_galleries['published_page']->id() . '/edit');
    $this->assertLinkByHref('gallery/' . $entity_galleries['published_article']->id() . '/edit');
    $this->assertNoLinkByHref('gallery/' . $entity_galleries['unpublished_page_1']->id() . '/edit');

    // Verify filtering by status and content type.
    $this->drupalGet('admin/content/gallery', array('query' => array('status' => TRUE, 'type' => 'page')));

    $this->assertLinkByHref('gallery/' . $entity_galleries['published_page']->id() . '/edit');
    $this->assertNoLinkByHref('gallery/' . $entity_galleries['published_article']->id() . '/edit');

    // Verify no operation links are displayed for regular users.
    $this->drupalLogout();
    $this->drupalLogin($this->baseUser1);
    $this->drupalGet('admin/content/gallery');
    $this->assertResponse(200);
    $this->assertLinkByHref('gallery/' . $entity_galleries['published_page']->id());
    $this->assertLinkByHref('gallery/' . $entity_galleries['published_article']->id());
    $this->assertNoLinkByHref('gallery/' . $entity_galleries['published_page']->id() . '/edit');
    $this->assertNoLinkByHref('gallery/' . $entity_galleries['published_page']->id() . '/delete');
    $this->assertNoLinkByHref('gallery/' . $entity_galleries['published_article']->id() . '/edit');
    $this->assertNoLinkByHref('gallery/' . $entity_galleries['published_article']->id() . '/delete');

    // Verify no unpublished content is displayed without permission.
    $this->assertNoLinkByHref('gallery/' . $entity_galleries['unpublished_page_1']->id());
    $this->assertNoLinkByHref('gallery/' . $entity_galleries['unpublished_page_1']->id() . '/edit');
    $this->assertNoLinkByHref('gallery/' . $entity_galleries['unpublished_page_1']->id() . '/delete');

    // Verify no tableselect.
    $this->assertNoFieldByName('entity_galleries[' . $entity_galleries['published_page']->id() . ']', '', 'No tableselect found.');

    // Verify unpublished content is displayed with permission.
    $this->drupalLogout();
    $this->drupalLogin($this->baseUser2);
    $this->drupalGet('admin/content/gallery');
    $this->assertResponse(200);
    $this->assertLinkByHref('gallery/' . $entity_galleries['unpublished_page_2']->id());
    // Verify no operation links are displayed.
    $this->assertNoLinkByHref('gallery/' . $entity_galleries['unpublished_page_2']->id() . '/edit');
    $this->assertNoLinkByHref('gallery/' . $entity_galleries['unpublished_page_2']->id() . '/delete');

    // Verify user cannot see unpublished content of other users.
    $this->assertNoLinkByHref('gallery/' . $entity_galleries['unpublished_page_1']->id());
    $this->assertNoLinkByHref('gallery/' . $entity_galleries['unpublished_page_1']->id() . '/edit');
    $this->assertNoLinkByHref('gallery/' . $entity_galleries['unpublished_page_1']->id() . '/delete');

    // Verify no tableselect.
    $this->assertNoFieldByName('entity_galleries[' . $entity_galleries['unpublished_page_2']->id() . ']', '', 'No tableselect found.');

    // Verify entity gallery access can be bypassed.
    $this->drupalLogout();
    $this->drupalLogin($this->baseUser3);
    $this->drupalGet('admin/content/gallery');
    $this->assertResponse(200);
    foreach ($entity_galleries as $entity_gallery) {
      $this->assertLinkByHref('gallery/' . $entity_gallery->id());
      $this->assertLinkByHref('gallery/' . $entity_gallery->id() . '/edit');
      $this->assertLinkByHref('gallery/' . $entity_gallery->id() . '/delete');
    }
  }

}
