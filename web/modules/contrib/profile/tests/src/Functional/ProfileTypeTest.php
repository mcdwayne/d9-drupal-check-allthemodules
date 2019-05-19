<?php

namespace Drupal\Tests\profile\Functional;

use Drupal\profile\Entity\ProfileType;
use Drupal\profile\Entity\Profile;

/**
 * Tests basic CRUD functionality of profile types.
 *
 * @group profile
 */
class ProfileTypeTest extends ProfileTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'access user profiles',
      'administer profile',
      'administer profile types',
      'administer profile fields',
      'administer profile display',
    ]);
  }

  /**
   * Verify that routes are created for the profile type.
   */
  public function testRoutes() {
    $this->drupalLogin($this->adminUser);
    $type = $this->createProfileType($this->randomMachineName());
    \Drupal::service('router.builder')->rebuildIfNeeded();
    $this->drupalGet("user/{$this->adminUser->id()}/{$type->id()}");
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests CRUD operations for profile types through the UI.
   */
  public function testUi() {
    $this->drupalLogin($this->adminUser);

    // Create a new profile type.
    $this->drupalGet('admin/config/people/profile-types');
    $this->assertSession()->statusCodeEquals(200);
    $this->clickLink(t('Add profile type'));

    $this->assertSession()->addressEquals('admin/config/people/profile-types/add');
    $edit = [
      'id' => 'customer',
      'label' => 'Customer',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertSession()->addressEquals('admin/config/people/profile-types');
    $this->assertSession()->pageTextContains('Customer profile type has been created.');
    $this->assertSession()->linkByHrefExists('admin/config/people/profile-types/manage/customer');
    $this->assertSession()->linkByHrefExists('admin/config/people/profile-types/manage/customer/fields');
    $this->assertSession()->linkByHrefExists('admin/config/people/profile-types/manage/customer/display');
    $this->assertSession()->linkByHrefExists('admin/config/people/profile-types/manage/customer/delete');

    // Edit the new profile type.
    $this->drupalGet("admin/config/people/profile-types/manage/customer");
    $this->getSession()->getPage()->checkField('Include in user registration form');
    $this->getSession()->getPage()->checkField('Create a new revision when a profile is modified');
    $this->submitForm([], 'Save');
    $this->assertSession()->addressEquals('admin/config/people/profile-types');
    $this->assertSession()->pageTextContains('Customer profile type has been updated.');

    $profile_type = ProfileType::load('customer');
    $this->assertEquals('Customer', $profile_type->label());
    $this->assertTrue($profile_type->getRegistration());
    $this->assertTrue($profile_type->shouldCreateNewRevision());

    // Delete profile type.
    // First check with existing profile of type.
    $profile = Profile::create([
      'type' => 'customer',
      'uid' => $this->adminUser->id(),
    ]);
    $profile->save();
    $this->drupalGet("admin/config/people/profile-types/manage/customer/delete");
    $this->assertSession()->pageTextContains('Customer is used by 1 profile on your site. You can not remove this profile type until you have removed all of the Customer profiles');

    // Delete profile and delete profile type.
    $profile->delete();
    $this->drupalGet("admin/config/people/profile-types/manage/customer/delete");
    $this->assertSession()->pageTextContains('This action cannot be undone.');
    $this->assertSession()->linkByHrefExists('admin/config/people/profile-types');
    $this->submitForm([], 'Delete');
    $this->assertSession()->addressEquals('admin/config/people/profile-types');
    $this->assertSession()->pageTextContains('The profile type Customer has been deleted.');

    $profile_type = ProfileType::load('customer');
    $this->assertNull($profile_type);
  }

}
