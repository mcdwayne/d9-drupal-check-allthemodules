<?php

namespace Drupal\people\Tests;

/**
 * Tests people type entity.
 *
 * @group people
 */
class PeopleTypeTest extends PeopleTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['block', 'views'];

  /**
   * A user with permission to administer people types.
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
      'administer people types',
    ]);
  }

  /**
   * Tests people type list, add, save.
   */
  public function testPeopleTypePage() {
    $this->drupalPlaceBlock('local_actions_block');

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/people/type');
    $this->assertResponse(200);
    $this->assertLinkByHref('admin/people/type/add');

    $this->clickLink(t('Add people type'));
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
