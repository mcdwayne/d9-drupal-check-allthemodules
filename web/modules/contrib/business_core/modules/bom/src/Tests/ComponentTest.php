<?php

namespace Drupal\bom\Tests;

/**
 * Tests bom_component entities.
 *
 * @group bom
 */
class ComponentTest extends BomTestBase {

  /**
   * A user with project admin permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['block', 'views'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser(['administer boms']);
  }

  /**
   * Test the list, add, save.
   */
  public function testList() {
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/bom/' . $this->bom->id());
    $this->assertText($this->bomComponent->get('item')->entity->label());
    $this->assertLinkByHref('admin/bom/' . $this->bom->id(), '/component/add');

    $this->clickLink(t('Add component'));
    $this->assertResponse(200);

    $edit = [
      'item[0][value]' => $this->item->label() . ' (' . $this->item->id() . ')',
      'quantity[0][value]' => 3,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);
  }

}
