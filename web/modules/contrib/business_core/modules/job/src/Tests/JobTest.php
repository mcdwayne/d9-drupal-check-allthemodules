<?php

namespace Drupal\job\Tests;

/**
 * Tests job entity.
 *
 * @group job
 */
class JobTest extends JobTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['block', 'views'];

  /**
   * A user with permission to administer job.
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
      'access job overview',
      'administer jobs',
    ]);
  }

  /**
   * Test list, add, edit.
   */
  public function testList() {
    $this->drupalPlaceBlock('local_actions_block');

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/job');
    $this->assertResponse(200);
    $this->assertLinkByHref('admin/job/add');

    $this->clickLink(t('Add job'));
    $this->assertResponse(200);

    $edit = [
      'title[0][value]' => $this->randomMachineName(8),
      'number[0][value]' => $this->randomMachineName(8),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);
    $this->assertText($edit['title[0][value]']);

    $this->drupalGet('admin/job/1/edit');
    $this->assertResponse(200);
    $edit = [
      'title[0][value]' => $this->randomMachineName(8),
      'number[0][value]' => $this->randomMachineName(8),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);
    $this->assertText($edit['title[0][value]']);
  }

}
