<?php

namespace Drupal\Tests\box\Functional;

use Drupal\box\Entity\BoxType;
use Drupal\Tests\BrowserTestBase;
use Drupal\Component\Utility\Unicode;

/**
 * Test to ensure that authorized users can add and remove box types.
 *
 * @group box
 */
class EntityTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['box'];

  /**
   * A user with permission to create and manage all boxes of given type.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $userAdmin;

  /**
   * A user with permission to create and manage own boxes of given type.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $userEditor;

  /**
   * The box storage.
   *
   * @var \Drupal\box\BoxStorageInterface
   */
  protected $boxStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->userAdmin = $this->drupalCreateUser([
      'access box overview',
      'create default box',
      'edit any default box',
      'delete any default box',
    ]);
    $this->userEditor = $this->drupalCreateUser([
      'access box overview',
      'create default box',
      'edit own default box',
      'delete own default box',
    ]);
    $this->boxStorage = $this->container->get('entity.manager')->getStorage('box');
  }

  /**
   * Tests that authorized users can add, edit and remove box entities.
   */
  public function testEntity() {
    // Test the add URL is not accessible to unauthorized users.
    $this->drupalGet('box/add/default');
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalLogin($this->userAdmin);

    // Add box entity.
    $edit = [];
    $edit['title[0][value]'] = $box_label = $this->randomString();
    $edit['machine_name[0][value]'] = $box_machine_name = Unicode::strtolower($this->randomMachineName());
    $edit['field_body[0][value]'] = $box_text = $this->randomString(50);
    $this->drupalPostForm('box/add/default', $edit, t('Save'));
    $this->assertSession()->responseContains(t('@type box %title has been created.', [
      '@type' => BoxType::load('default')->label(),
      '%title' => $box_label,
    ]));

    // Get box ID.
    $box = $this->boxStorage::loadByMachineName($box_machine_name);
    $this->assertTrue($box, 'Box found in database');
    $box_id = $box->id();

    // Test the edit and delete URLs are not accessible to unauthorized users.
    $this->drupalLogout();
    $this->drupalLogin($this->userEditor);
    $this->drupalGet("box/{$box_id}");
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet("box/{$box_id}/delete");
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalLogout();
    $this->drupalLogin($this->userAdmin);

    // Edit box entity.
    $edit = [];
    $edit['title[0][value]'] = $box_label_updated = $this->randomString();
    $edit['field_body[0][value]'] = $box_text_new = $this->randomString(50);
    $this->drupalPostForm("box/{$box_id}", $edit, t('Save'));
    $this->assertSession()->responseContains(t('Box %title has been updated.', [
      '%title' => $box_label_updated,
    ]));

    // Delete box entity.
    $this->drupalPostForm("box/{$box_id}/delete", [], t('Delete'));
    $this->assertSession()->responseContains(t('The @entity-type %label has been deleted.', [
      '@entity-type' => 'box',
      '%label' => $box_label_updated,
    ]));
  }

}
