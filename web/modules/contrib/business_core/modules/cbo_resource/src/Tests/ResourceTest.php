<?php

namespace Drupal\cbo_resource\Tests;

/**
 * Tests resource entity.
 *
 * @group cbo_resource
 */
class ResourceTest extends ResourceTestBase {

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
      'administer resources',
      'access resource',
    ]);
  }

  /**
   * Test list, add, edit.
   */
  public function testList() {
    $this->drupalPlaceBlock('local_actions_block');

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/resource');
    $this->assertResponse(200);
    $this->assertLinkByHref('admin/resource/add');

    $this->clickLink(t('Add resource'));
    $this->assertResponse(200);
    $this->assertLinkByHref('admin/resource/add/supplier');

    $this->clickLink(t('Supplier'));
    $this->assertResponse(200);
    $edit = [
      'title[0][value]' => $this->randomMachineName(8),
      'number[0][value]' => $this->randomMachineName(8),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);
    $this->assertText($edit['title[0][value]']);
  }

  /**
   * Tests edit page.
   */
  public function testEdit() {
    $this->drupalPlaceBlock('local_tasks_block');

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/resource/' . $this->resource->id());
    $this->assertResponse(200);
    $this->assertLinkByHref('admin/resource/' . $this->resource->id() . '/edit');

    $this->clickLink(t('Edit'));
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
