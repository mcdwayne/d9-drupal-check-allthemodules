<?php

namespace Drupal\job\Tests;

/**
 * Tests job group entity.
 *
 * @group job
 */
class JobGroupTest extends JobTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['block', 'views'];

  /**
   * A user with permission to administer job groups.
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
      'administer job groups',
    ]);
  }

  /**
   * Tests job group list, add, save.
   */
  public function testJobGroupPage() {
    $this->drupalPlaceBlock('local_actions_block');

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/job/group');
    $this->assertResponse(200);
    $this->assertLinkByHref('admin/job/group/add');

    $this->clickLink(t('Add job group'));
    $this->assertResponse(200);

    $edit = [
      'label' => $this->randomMachineName(8),
      'id' => strtolower($this->randomMachineName(8)),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);
    $this->assertText($edit['label']);
  }

}
