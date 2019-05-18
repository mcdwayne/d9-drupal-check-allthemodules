<?php

namespace Drupal\Tests\media_private_access\Functional;

use Drupal\media\Entity\Media;
use Drupal\media_private_access\MediaPrivateAccessControlHandler;
use Drupal\Tests\media\Functional\MediaFunctionalTestBase;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Tests related to the Permission-based access mode.
 *
 * @group media_private_access
 */
class MediaPrivateAccessPermissionsTest extends MediaFunctionalTestBase {

  use AssertPageCacheContextsAndTagsTrait;

  /**
   * A non-admin test user account.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $nonAdminUser1;

  /**
   * A non-admin test user account.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $nonAdminUser2;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'block',
    'media_test_source',
    'media_private_access',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // This is needed to provide the user cache context for a below assertion.
    $this->drupalPlaceBlock('local_tasks_block');
    $this->nonAdminUser1 = $this->drupalCreateUser([]);
    $this->nonAdminUser2 = $this->drupalCreateUser([]);
  }

  /**
   * Test the permission-based media access mode.
   */
  public function testPermissionBasedMediaAccess() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $media_type = $this->createMediaType();

    // Create media.
    $media = Media::create([
      'bundle' => $media_type->id(),
      'name' => 'Generic media asset',
    ]);
    $media->save();
    $user_media = Media::create([
      'bundle' => $media_type->id(),
      'name' => 'Authored media asset',
      'uid' => $this->nonAdminUser2->id(),
    ]);
    $user_media->save();

    // Before configuring anything, both admins and non-admins have access to
    // all assets.
    $this->drupalGet('media/' . $media->id());
    $this->assertCacheContext('user.permissions');
    $assert_session->statusCodeEquals(200);
    $this->drupalGet('media/' . $user_media->id());
    $this->assertCacheContext('user.permissions');
    $assert_session->statusCodeEquals(200);
    $this->drupalLogin($this->nonAdminUser1);
    $this->drupalGet('media/' . $media->id());
    $this->assertCacheContext('user.permissions');
    $assert_session->statusCodeEquals(200);
    $this->drupalGet('media/' . $user_media->id());
    $this->assertCacheContext('user.permissions');
    $assert_session->statusCodeEquals(200);

    // Set access mode on our type to be "Permission-based".
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/config/media/media-private-access');
    $page->selectFieldOption($media_type->label(), MediaPrivateAccessControlHandler::MEDIA_PRIVATE_ACCESS_PERMISSION);
    $page->pressButton('Save configuration');

    // Now only the admin should have access to both assets.
    $this->drupalGet('media/' . $media->id());
    $this->assertCacheContext('user.permissions');
    $assert_session->statusCodeEquals(200);
    $this->drupalGet('media/' . $user_media->id());
    $this->assertCacheContext('user.permissions');
    $assert_session->statusCodeEquals(200);
    $this->drupalLogin($this->nonAdminUser1);
    $this->drupalGet('media/' . $media->id());
    $this->assertCacheContext('user.permissions');
    $assert_session->statusCodeEquals(403);
    $this->drupalGet('media/' . $user_media->id());
    $this->assertCacheContext('user.permissions');
    $assert_session->statusCodeEquals(403);

    // Owners, however, have access to their own entities without the specific
    // permission.
    $this->drupalLogin($this->nonAdminUser2);
    $this->drupalGet('media/' . $media->id());
    $this->assertCacheContext('user.permissions');
    $assert_session->statusCodeEquals(403);
    $this->drupalGet('media/' . $user_media->id());
    $this->assertCacheContext('user.permissions');
    $assert_session->statusCodeEquals(200);

    // Users with the specific permission can view the asset even if not admins
    // nor owners.
    /** @var \Drupal\user\RoleInterface $role */
    $role = Role::load(RoleInterface::AUTHENTICATED_ID);
    $this->grantPermissions($role, ["view {$media_type->id()} media"]);
    $this->drupalLogin($this->nonAdminUser1);
  }

}
