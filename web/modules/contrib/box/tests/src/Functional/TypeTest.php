<?php

namespace Drupal\Tests\box\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Component\Utility\Unicode;

/**
 * Test to ensure that authorized user can add and remove box types.
 *
 * @group box
 */
class TypeTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['box'];

  /**
   * A user with permission to administer boxes.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * A user with permission to create default and custom box.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user_editor;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->user = $this->drupalCreateUser(['administer box entities']);
  }

  /**
   * Tests that authorized user can add and remove box type.
   */
  public function testType() {
    // Test the add URL is not accessible to unauthorized users.
    $this->drupalGet('admin/structure/box-types/add');
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalLogin($this->user);

    // Add box type.
    $edit = [];
    $edit['label'] = $box_type_label = $this->randomString();
    $edit['id'] = $box_type_id = Unicode::strtolower($this->randomMachineName());
    $edit['title_label'] = $this->randomString();
    $this->drupalPostForm('admin/structure/box-types/add', $edit, t('Save'));
    $this->assertSession()->responseContains(t('Created the %label Box type.', [
      '%label' => $box_type_label,
    ]));

    // Check whether both default and new type are available on box add page.
    $this->user_editor = $this->drupalCreateUser(["create default box", "create {$box_type_id} box"]);
    $this->drupalLogout();
    $this->drupalLogin($this->user_editor);
    $this->drupalGet('box/add');
    $this->assertSession()->pageTextContains('default');
    $this->assertSession()->pageTextContains($box_type_label);
    $this->drupalLogout();
    $this->drupalLogin($this->user);

    // Delete box type.
    $this->drupalPostForm("admin/structure/box-types/manage/{$box_type_id}/delete", [], t('Delete'));
    $this->assertSession()->responseContains(t('The @entity-type %label has been deleted.', [
      '@entity-type' => 'box type',
      '%label' => $box_type_label,
    ]));
  }

}
