<?php

namespace Drupal\cbo_resource\Tests;

/**
 * Tests resource type entity.
 *
 * @group cbo_resource
 */
class ResourceTypeTest extends ResourceTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['block', 'views'];

  /**
   * A user with permission to administer resource types.
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
      'administer resource types',
    ]);
  }

  /**
   * Tests resource type list, add, save.
   */
  public function testResourceTypePage() {
    $this->drupalPlaceBlock('local_actions_block');

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/resource/type');
    $this->assertResponse(200);
    $this->assertLinkByHref('admin/resource/type/add');

    $this->clickLink(t('Add resource type'));
    $this->assertResponse(200);
    $edit = [
      'id' => strtolower($this->randomMachineName(8)),
      'label' => $this->randomMachineName(8),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);
    $this->assertText($edit['label']);
  }

}
