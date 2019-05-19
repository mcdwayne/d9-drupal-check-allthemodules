<?php

namespace Drupal\Tests\snippet_manager\Functional;

/**
 * Test access to administrative pages.
 *
 * @group snippet_manager
 */
class AdminPagesAccessTest extends TestBase {

  /**
   * Test callback.
   */
  public function testAdminPages() {
    $pages = [
      'admin/structure/snippet',
      'admin/structure/snippet/add',
      'admin/structure/snippet/alpha',
      'admin/structure/snippet/alpha/source',
      'admin/structure/snippet/alpha/edit',
      'admin/structure/snippet/alpha/edit/template',
      'admin/structure/snippet/alpha/edit/css',
      'admin/structure/snippet/alpha/edit/js',
      'admin/structure/snippet/alpha/edit/variable/add',
      'admin/structure/snippet/alpha/edit/variable/foo/edit',
      'admin/structure/snippet/alpha/edit/variable/foo/delete',
      'admin/structure/snippet/alpha/delete',
      'admin/structure/snippet/alpha/duplicate',
      'admin/structure/snippet/path-autocomplete',
    ];

    $assert_session = $this->assertSession();

    // Ensure that admin user has access to all module pages.
    foreach ($pages as $page) {
      $this->drupalGet($page);
      $assert_session->statusCodeEquals(200);
    }

    // Test that unprilivged user has no access to snippet manager pages.
    $web_user = $this->drupalCreateUser();
    $this->drupalLogin($web_user);
    foreach ($pages as $page) {
      $this->drupalGet($page);
      $assert_session->statusCodeEquals(403);
    }

  }

}
