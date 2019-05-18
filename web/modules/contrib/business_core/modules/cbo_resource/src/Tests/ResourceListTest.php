<?php

namespace Drupal\cbo_resource\Tests;

/**
 * Tests resource list entity.
 *
 * @group cbo_resource
 */
class ResourceListTest extends ResourceTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['block', 'views'];

  /**
   * A user with permission to administer resource.
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
      'administer resource lists',
      'access resource list',
    ]);
  }

  /**
   * Test list, add, edit.
   */
  public function testList() {
    $this->drupalPlaceBlock('local_actions_block');

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/resource/list');
    $this->assertResponse(200);
    $this->assertLinkByHref('admin/resource/list/add');

    $this->clickLink(t('Add resource list'));
    $this->assertResponse(200);

    $edit = [
      'title[0][value]' => $this->randomMachineName(8),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);
    $this->assertText($edit['title[0][value]']);
  }

}
