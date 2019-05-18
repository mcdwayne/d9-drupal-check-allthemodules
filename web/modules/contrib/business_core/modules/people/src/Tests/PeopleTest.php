<?php

namespace Drupal\people\Tests;

/**
 * Tests people entity.
 *
 * @group people
 */
class PeopleTest extends PeopleTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['block', 'views'];

  /**
   * A user with permission to administer people.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'administer peoples',
      'access people',
      'access organization',
    ]);
    $this->adminUser->people->target_id = $this->people->id();
    $this->adminUser->save();
  }

  /**
   * Test list, add, save.
   */
  public function testList() {
    $this->drupalPlaceBlock('local_actions_block');

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/resource/people');
    $this->assertResponse(200);
    $this->assertLinkByHref('admin/resource/people/add');
    $this->assertLink($this->people->label());

    $this->clickLink(t('Add people'));
    $this->assertResponse(200);
    $this->assertLinkByHref('admin/resource/people/add/employee');

    $this->clickLink(t('Employee'));
    $this->assertResponse(200);
    $edit = [
      'first_name[0][value]' => $this->randomMachineName(8),
      'last_name[0][value]' => $this->randomMachineName(8),
      'title[0][value]' => $this->randomMachineName(8),
      'organization[0][target_id]' => $this->organization->label() . ' (' . $this->organization->id() . ')',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);
    $this->assertText($edit['last_name[0][value]']);
  }

  /**
   * Tests display page.
   */
  public function testDisplay() {
    $this->adminUser->people->target_id = $this->people->id();
    $this->adminUser->save();

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/resource/people/' . $this->people->id());
    $this->assertResponse(200);
    $this->assertLink($this->adminUser->label(), 0, 'User roles displayed');
  }

  /**
   * Tests edit page.
   */
  public function testEdit() {
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/resource/people/' . $this->people->id() . '/edit');
    $this->assertResponse(200);

    $edit = [
      'last_name[0][value]' => $this->randomMachineName(8),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);
    $this->assertText($edit['last_name[0][value]']);
  }

}
