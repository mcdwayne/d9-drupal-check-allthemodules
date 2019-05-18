<?php

namespace Drupal\Tests\menu_svg_icons\Functional;

use Drupal\menu_svg_icons\Entity\IconSet;
use Drupal\Tests\BrowserTestBase;

/**
 * Test menu svg admin UI.
 *
 * @group menu_svg_icons
 */
class MenuSvgIconsUiTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['menu_svg_icons', 'block'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Make sure local actions are available as links.
    $this->drupalPlaceBlock('local_actions_block');
  }

  /**
   * Test icon set admin actions namely; creation and deletion.
   */
  public function testIconSetCreation() {
    // Create a new user which has access to the menu svg icon settings.
    $this->drupalLogin($this->createUser(['administer site configuration']));
    $this->drupalGet('admin/config/media/menu-svg-icons/icon-set');

    // Test to creating a new icon set.
    $this->assertSession()->linkByHrefExists('/admin/config/media/menu-svg-icons/icon-set/add');
    $this->clickLink('Add icon set');
    $this->submitForm([
      'label' => 'Test',
      'description' => '',
      'placement' => 'left',
      'source' => 'icon source',
      'icon_height' => '',
      'icon_width' => '',
    ], 'Save');

    $this->assertSession()->pageTextContains('Saved the Test Icon set.');
    $this->assertSession()->addressEquals('admin/config/media/menu-svg-icons/icon-set');

    // Test deleting the newly created icon set.
    $this->assertSession()->linkByHrefExists('/admin/config/media/menu-svg-icons/icon-set/test/delete');
    // Index 1 as the module comes with a default 'Arrow' icon set.
    $this->clickLink('Delete', 1);
    $this->assertSession()->pageTextContains('Are you sure you want to delete the Test icon set?');
    $this->clickLink('Delete');
    $this->assertSession()->pageTextContains('Test icon set has been deleted.');

    // Make sure the entity was actually deleted.
    $this->assertNull(IconSet::load('test'), 'Icon Set entity "Test" does not exist');
  }

}
