<?php

namespace Drupal\quick_code\Tests;

/**
 * Tests quick_code entity.
 *
 * @group quick_code
 */
class QuickCodeTest extends QuickCodeTestBase {
  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['block', 'views'];

  /**
   * A user with permission to administer quick code.
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
      'administer quick codes',
      'access quick code',
    ]);
  }

  /**
   * Tests the list, add, save.
   */
  public function testList() {
    $this->drupalPlaceBlock('local_actions_block');

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/quick_code/type/' . $this->quickCodeType->id());
    $this->assertResponse(200);
    $this->assertLink($this->quickCode->label());
    $this->assertLink(t('Add %type', ['%type' => $this->quickCodeType->label()]));

    // $this->clickLink(t('Add %type', ['%type' => $this->quickCodeType->label()]));
    $this->drupalGet('admin/quick_code/add/' . $this->quickCodeType->id());
    $this->assertResponse(200);

    $edit = [
      'label[0][value]' => $this->randomMachineName(8),
      'description[0][value]' => $this->randomMachineName(8),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);
    $this->assertText($edit['label[0][value]']);
  }

  /**
   * Tests the edit form.
   */
  public function testEdit() {
    $this->drupalPlaceBlock('local_tasks_block');

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/quick_code/' . $this->quickCode->id());
    $this->assertResponse(200);
    $this->assertLinkByHref('admin/quick_code/' . $this->quickCode->id() . '/edit');

    $this->clickLink(t('Edit'));
    $this->assertResponse(200);

    $edit = [
      'label[0][value]' => '阿斯顿发',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);
    $this->assertText($edit['label[0][value]']);

    $entity = \Drupal::entityTypeManager()->getStorage('quick_code')
      ->load($this->quickCode->id());
    $this->assertEqual($entity->quick_code->value, 'asdf', 'Generated the quick code automatically.');
  }

  /**
   * Tests the views bulk form.
   */
  public function testViewsBulkForm() {
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/quick_code/type/' . $this->quickCodeType->id(), [
      'query' => ['effective' => 0],
    ]);
    $this->assertRaw('effective');

    $edit = [
      'bulk_form[0]' => TRUE,
      'action' => 'quick_code_disable_action',
    ];
    $this->drupalPostForm(NULL, $edit, t('Apply to selected items'));
    $this->assertRaw('invalid');
  }

}
