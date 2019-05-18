<?php

namespace Drupal\Tests\flexiform\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Flexiform functional test.
 *
 * @group flexiform
 */
class FlexiformEntityFormTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'field_ui', 'flexiform_test'];

  /**
   * A user with permission to bypass access content.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $permissions = [
      'administer nodes',
      'administer node form display',
      'create article content',
      'edit any article content',
      'delete any article content',
    ];
    $this->adminUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests that the Article 'Manage form display' page works without Flexiform.
   */
  public function testArticleManageFormDisplayPageBeforeFlexiform() {
    $this->drupalGet('admin/structure/types/manage/article/form-display');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests that the Article 'Manage form display' page works with Flexiform.
   */
  public function testArticleManageFormDisplayPageAfterFlexiform() {
    // Install Flexiform module.
    \Drupal::service('module_installer')->install(['flexiform']);

    $this->drupalGet('admin/structure/types/manage/article/form-display');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests basic form CRUD functionality on Article nodes.
   */
  public function testFormCrud() {
    $this->doFormCRUD();
  }

  /**
   * Tests basic form CRUD functionality on Article nodes, with Flexiform.
   */
  public function testFormCrudFlexiform() {
    // Install Flexiform module.
    \Drupal::service('module_installer')->install(['flexiform']);

    $this->doFormCRUD();
  }

  /**
   * Executes the form CRUD tests.
   */
  protected function doFormCrud() {
    $title1 = $this->randomMachineName(8);
    $title2 = $this->randomMachineName(10);

    $edit = [
      'title[0][value]' => $title1,
      'body[0][value]' => $this->randomString(127),
    ];

    $this->drupalPostForm('node/add/article', $edit, t('Save'));
    $node = $this->drupalGetNodeByTitle($title1, TRUE);
    $this->assertTrue($node, 'Article node found in the database.');

    $edit['title[0][value]'] = $title2;
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));

    $node = $this->drupalGetNodeByTitle($title1, TRUE);
    $this->assertFalse($node, 'The Article node has been modified.');

    $node = $this->drupalGetNodeByTitle($title2, TRUE);
    $this->assertTrue($node, 'Modified Article node found in the database.');
    $this->assertNotEquals($node->label(), $title1, 'The Article node name has been modified.');

    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->clickLink(t('Delete'));
    $this->drupalPostForm(NULL, [], t('Delete'));
    $node = $this->drupalGetNodeByTitle($title2, TRUE);
    $this->assertFalse($node, 'Article node not found in the database.');
  }

}
