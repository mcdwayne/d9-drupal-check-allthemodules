<?php

namespace Drupal\Tests\menu_link_weight\FunctionalJavascript;

use Drupal\Core\Menu\MenuLinkInterface;
use Drupal\node\Entity\NodeType;

/**
 * Tests the functionality of the Menu Link Weight module.
 *
 * @group menu_link_weight
 */
class MenuLinkWeightMenuUiJavascriptTest extends MenuLinkWeightJavascriptTestBase {

  /**
   * Test creating, editing, deleting menu links via menu link form.
   */
  public function testMenuFunctionality() {
    // Create a node.
    $node1_title = '1 ' . $this->randomMachineName();

    /** @var \Drupal\node\NodeInterface $node1 */
    $node1 = $this->drupalCreateNode(['type' => $this->nodeType, 'title' => $node1_title]);

    // Assert that there is no link for the node.
    $this->assertFalse($this->loadMenuLinkByNode($node1));

    $edit = [
      'enabled[value]' => 1,
      'title[0][value]' => $node1_title,
      'link[0][uri]' => '/' . $node1->toUrl()->getInternalPath(),
    ];
    $this->drupalPostForm('admin/structure/menu/manage/tools/add', $edit, 'Save');
    $this->assertTrue($this->loadMenuLinkByNode($node1));
    $this->assertInstanceOf(MenuLinkInterface::class, $this->loadMenuLinkByNode($node1));
    $node1_link = $this->loadMenuLinkByNode($node1);

    // Edit the node and create a menu link.
    $this->drupalGet($node1_link->getEditRoute());
    // Test the hidden fields validation. This should pass:
    $this->assertSession()->hiddenFieldExists('db_weights[node.add_page]')->setValue(-49);
    $this->assertSession()->hiddenFieldExists('db_weights[filter.tips_all]')->setValue(-48);
    $edit = array(
      'menu[menu_link_weight][node.add_page][weight]' => '-50',
      'menu[menu_link_weight][link_current][weight]' => '-49',
      'menu[menu_link_weight][filter.tips_all][weight]' => '-48',
    );
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertSession()->pageTextNotContains('The menu link weights have been changed by another user, please try again.');

    /** @var \Drupal\menu_link_content\MenuLinkContentInterface[] $links */
    $links = $this->contentMenuLinkStorage->loadByProperties(['title' => $node1_title]);
    $link1_id = reset($links)->getPluginId();

    // Assert that the reordering was successful.
    $this->assertLinkWeight('node.add_page', -50);
    $this->assertLinkWeight($link1_id, -49);
    $this->assertLinkWeight('filter.tips_all', -48);

    $this->drupalGet($node1_link->getEditRoute());
    $this->assertSession()->elementContains('css', '.menu-link-weight-link-current', $node1_title);
    $this->assertSession()->pageTextContains('(provided menu link)');
    $this->assertSession()->pageTextContains('Change the weight of the links within the Tools menu');
    $option = $this->assertSession()->optionExists('edit-menu-menu-link-weight-link-current-weight', -49);
    $this->assertTrue($option->hasAttribute('selected'));

    // Test Ajax functionality.
    $select_xpath = $this->cssSelectToXpath('[data-drupal-selector="edit-menu-parent"]');
    $this->getSession()->getDriver()->selectOption($select_xpath, 'tools:node.add_page');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains('Change the weight of the links within the Add content menu');

    // Create a node.
    $node2_title = '2 ' . $this->randomMachineName();
    /** @var \Drupal\node\NodeInterface $node1 */
    $node2 = $this->drupalCreateNode(['type' => $this->nodeType, 'title' => $node2_title]);

    // Assert that there is no link for the node.
    $this->assertFalse($this->loadMenuLinkByNode($node2));

    // Create new menu link for node 2 and rearrange the existing ones.
    $edit = array(
      'title[0][value]' => $node2_title,
      'link[0][uri]' => '/' . $node2->toUrl()->getInternalPath(),
      'menu[menu_link_weight][' . $link1_id . '][weight]' => '-50',
      'menu[menu_link_weight][filter.tips_all][weight]' => '-49',
      'menu[menu_link_weight][link_current][weight]' => '-48',
      'menu[menu_link_weight][node.add_page][weight]' => '-47',
    );
    $this->drupalPostForm('admin/structure/menu/manage/tools/add', $edit, 'Save');

    // Assert that the link exists.
    $this->assertInstanceOf(MenuLinkInterface::class, $this->loadMenuLinkByNode($node2));
    $node2_link = $this->loadMenuLinkByNode($node2);
    $link2_id = $node2_link->getPluginId();

    // Assert that the reordering was successful.
    $this->assertLinkWeight($link1_id, -50);
    $this->assertLinkWeight('filter.tips_all', -49);
    $this->assertLinkWeight($link2_id, -48);
    $this->assertLinkWeight('node.add_page', -47);

    $this->drupalGet($node2_link->getEditRoute());
    $this->assertSession()->elementContains('css', '.menu-link-weight-link-current', $node2_title);
    $this->assertSession()->pageTextContains('(provided menu link)');
    $option = $this->assertSession()->optionExists('edit-menu-menu-link-weight-link-current-weight', -48);
    $this->assertTrue($option->hasAttribute('selected'));

    // Assert that the item is placed on top of the list if no other options
    // are selected.
    $node3_title = '3 ' . $this->randomMachineName();
    /** @var \Drupal\node\NodeInterface $node1 */
    $node3 = $this->drupalCreateNode(['type' => $this->nodeType, 'title' => $node3_title]);

    $edit = array(
      'title[0][value]' => $node3_title,
      'link[0][uri]' => '/' . $node3->toUrl()->getInternalPath(),
    );
    $this->drupalPostForm('admin/structure/menu/manage/tools/add', $edit, 'Save');

    // Assert that the link exists.
    $this->assertInstanceOf(MenuLinkInterface::class, $this->loadMenuLinkByNode($node3));
    $node3_link = $this->loadMenuLinkByNode($node3);
    $link3_id = $node3_link->getPluginId();

    // Assert that the reordering was successful.
    $this->assertLinkWeight($link3_id, -50);
    $this->assertLinkWeight($link1_id, -49);
    $this->assertLinkWeight('filter.tips_all', -48);
    $this->assertLinkWeight($link2_id, -47);
    $this->assertLinkWeight('node.add_page', -46);

    // Test the custom tree reordering functionality:
    $this->moduleInstaller->install(array('menu_link_weight_test'));
    // Insert the new link above item 2:
    $this->state->set('menu_link_weight_test_parent_value', 'tools:');
    $this->state->set('menu_link_weight_test_relative_position', 'above_' . $link2_id);

    $node4_title = '4 ' . $this->randomMachineName();
    /** @var \Drupal\node\NodeInterface $node1 */
    $node4 = $this->drupalCreateNode(['type' => $this->nodeType, 'title' => $node4_title]);
    $edit = array(
      'title[0][value]' => $node4_title,
      'link[0][uri]' => '/' . $node4->toUrl()->getInternalPath(),
    );
    $this->drupalPostForm('admin/structure/menu/manage/tools/add', $edit, 'Save');
    $link4_id = $this->loadMenuLinkByNode($node4)->getPluginId();

    $this->assertLinkWeight($link3_id, -50);
    $this->assertLinkWeight($link1_id, -49);
    $this->assertLinkWeight('filter.tips_all', -48);
    $this->assertLinkWeight($link4_id, -47);
    $this->assertLinkWeight($link2_id, -46);
    $this->assertLinkWeight('node.add_page', -45);

    $this->state->set('menu_link_weight_test_relative_position', 'below_' . $link2_id);
    $node5_title = '5 ' . $this->randomMachineName();
    $node5 = $this->drupalCreateNode(['type' => $this->nodeType, 'title' => $node5_title]);
    $edit = array(
      'title[0][value]' => $node5_title,
      'link[0][uri]' => '/' . $node5->toUrl()->getInternalPath(),
    );
    $this->drupalPostForm('admin/structure/menu/manage/tools/add', $edit, 'Save');
    $link5_id = $this->loadMenuLinkByNode($node5)->getPluginId();

    // Assert that the reordering was successful.
    $this->assertLinkWeight($link3_id, -50);
    $this->assertLinkWeight($link1_id, -49);
    $this->assertLinkWeight('filter.tips_all', -48);
    $this->assertLinkWeight($link4_id, -47);
    $this->assertLinkWeight($link2_id, -46);
    $this->assertLinkWeight($link5_id, -45);
    $this->assertLinkWeight('node.add_page', -44);

    // Rearrange menu link on a default menu link form.
    $edit = array(
      'menu[menu_link_weight][' . $link1_id . '][weight]' => '1',
      'menu[menu_link_weight][' . $link2_id . '][weight]' => '2',
      'menu[menu_link_weight][' . $link3_id . '][weight]' => '3',
      'menu[menu_link_weight][' . $link4_id . '][weight]' => '4',
      'menu[menu_link_weight][' . $link5_id . '][weight]' => '5',
      'menu[menu_link_weight][link_current][weight]' => '6',
      'menu[menu_link_weight][node.add_page][weight]' => '7',
    );
    $this->drupalPostForm('admin/structure/menu/link/filter.tips_all/edit', $edit, 'Save');

    $this->assertLinkWeight($link1_id, 1);
    $this->assertLinkWeight($link2_id, 2);
    $this->assertLinkWeight($link3_id, 3);
    $this->assertLinkWeight($link4_id, 4);
    $this->assertLinkWeight($link5_id, 5);
    $this->assertLinkWeight('filter.tips_all', 6);
    $this->assertLinkWeight('node.add_page', 7);
  }

}
