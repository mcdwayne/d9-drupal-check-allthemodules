<?php

namespace Drupal\Tests\blockgroup\Functional;

use Drupal\Component\Utility\Html;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the access of block groups and CRUD.
 *
 * @group blockgroup
 */
class BlockGroupTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['block', 'blockgroup'];

  /**
   * Creates a block group and verifies its consistency.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testBlockGroupUserAccess() {

    // Anonymous users can't access the page.
    $this->drupalGet('admin/structure/block_group_content');
    $this->assertSession()->statusCodeEquals(403);

    // Authenticated users without the "administer blocks" permission can't
    // access the page.
    $web_user1 = $this->drupalCreateUser();
    $this->drupalLogin($web_user1);
    $this->drupalGet('admin/structure/block_group_content');
    $this->assertSession()->statusCodeEquals(403);

    // Authenticated users with "administer blocks" permission can access the
    // page.
    $web_user2 = $this->drupalCreateUser(['administer blockgroups']);
    $this->drupalLogin($web_user2);
    $this->drupalGet('admin/structure/block_group_content');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests creating a block group programmatically.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function testBlockGroupCreation() {

    $web_user = $this->drupalCreateUser(['administer blockgroups']);
    $this->drupalLogin($web_user);

    // Verify that we can manage entities through the user interface.
    $blockgroup_label = 'group_test';
    $blockgroup_machine_name = 'group_test';
    $this->drupalPostForm(
      '/admin/structure/block_group_content/add',
      [
        'label' => $blockgroup_label,
        'id' => $blockgroup_machine_name,
      ],
      t('Save')
    );
    // Check if the created blockgroup is in the list.
    $this->drupalGet('/admin/structure/block_group_content');
    $this->assertSession()->pageTextContains($blockgroup_machine_name);
    // Verify that "Group test" blockgroup is editable.
    $this->drupalGet('/admin/structure/block_group_content/' . $blockgroup_machine_name);
    $this->assertSession()->fieldExists('label');
    // Verify that "Group test" blockgroup can be deleted.
    $this->drupalPostForm(
      '/admin/structure/block_group_content/' . $blockgroup_machine_name . '/delete',
      [],
      t('Delete')
    );
  }

  /**
   * Tests creating a block group programmatically.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testBlockGroupDisplay() {

    $this->drupalLogin($this->rootUser);

    $theme = $this->config('system.theme')->get('default');

    // Create a block group..
    $blockgroup_label = 'TestBlockGroup';
    $blockgroup_machine_name = 'block_group_test';
    $block_group = [
      'label' => $blockgroup_label,
      'id' => $blockgroup_machine_name,
    ];
    $this->drupalPostForm(
      '/admin/structure/block_group_content/add',
      $block_group,
      t('Save')
    );
    // Check if the created blockgroup is in the list.
    $this->drupalGet('/admin/structure/block_group_content');
    $this->assertSession()->pageTextContains($blockgroup_machine_name);

    // Check if the region for the created blockgroup is in on the block page.
    $this->drupalGet("admin/structure/block/list/$theme");
    $this->assertSession()->pageTextContains("Block group: $blockgroup_label");

    // Place a block in that region.
    $block_label = 'BlockInBlockGroup';
    $block_id = 'block_in_block_group';
    $block = [];
    $block['id'] = $block_id;
    $block['theme'] = $theme;
    $block['region'] = $blockgroup_machine_name;
    $block['settings[label]'] = $block_label;
    $block['settings[label_display]'] = TRUE;
    $this->drupalPostForm('admin/structure/block/add/system_powered_by_block', $block, t('Save block'));
    $this->assertSession()->addressEquals("admin/structure/block/list/$theme?block-placement=" . Html::getClass($block['id']));

    // Check that the block is not visible on the front page.
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains($block_label);

    // Place the block group's block in the sidebar.
    $group_block_label = 'TheGroupBlock';
    $group_block = [];
    $group_block['id'] = 'the_group_block';
    $group_block['theme'] = $theme;
    $group_block['region'] = 'content';
    $group_block['settings[label]'] = $group_block_label;
    $group_block['settings[label_display]'] = TRUE;
    $this->drupalPostForm("admin/structure/block/add/block_group:$blockgroup_machine_name", $group_block, t('Save block'));
    $this->assertSession()->addressEquals("admin/structure/block/list/$theme?block-placement=" . Html::getClass($group_block['id']));

    // Check that the block is visible on the front page.
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($block_label);

  }

}
