<?php

namespace Drupal\Tests\snippet_manager\Functional;

/**
 * Tests view variable plugin.
 *
 * @group snippet_manager
 */
class ViewVariableTest extends TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'snippet_manager',
    'snippet_manager_test',
    'views',
    'views_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected static $permissions = ['access user profiles', 'administer views'];

  /**
   * Test callback.
   */
  public function testViewVariable() {

    $edit = [
      'plugin_id' => 'view:who_s_online',
      'name' => 'who_s_online',
    ];
    $this->drupalPostForm('admin/structure/snippet/alpha/edit/variable/add', $edit, 'Save and continue');
    $this->assertStatusMessage('The variable has been created.');

    $edit = [
      'configuration[display]' => 'default',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    $edit = [
      'template[value]' => '<div class="snippet-content">{{ who_s_online }}</div>',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    $this->drupalGet('admin/structure/snippet/alpha');

    $xpath = '//div[@class = "snippet-content"]/div[@class = "views-element-container"]/div[contains(@class, "%s")]/div[@class = "view-content"]';

    $this->assertXpath(sprintf($xpath, 'view-display-id-default'));

    // Change view display and test view appearance.
    $edit = [
      'configuration[display]' => 'who_s_online_block',
    ];
    $this->drupalPostForm('admin/structure/snippet/alpha/edit/variable/who_s_online/edit', $edit, 'Save');
    $this->drupalGet('admin/structure/snippet/alpha');

    $this->assertXpath(sprintf($xpath, 'view-display-id-who_s_online_block'));

    // Test views edit operation.
    $this->drupalGet('admin/structure/snippet/alpha/edit/template');
    $this->click('//ul[@class = "dropbutton"]//a[text() = "Edit view"]');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('admin/structure/views/view/who_s_online/edit/who_s_online_block');
    $this->assertPageTitle("Who's online block (User)");
  }

}
