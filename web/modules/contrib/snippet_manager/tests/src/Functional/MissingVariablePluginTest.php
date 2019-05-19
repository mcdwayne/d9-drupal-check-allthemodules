<?php

namespace Drupal\Tests\snippet_manager\Functional;

/**
 * Tests missing variable plugin.
 *
 * @group snippet_manager
 */
class MissingVariablePluginTest extends TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'snippet_manager',
    'snippet_manager_test',
    'views',
  ];

  /**
   * Tests missing variable plugin.
   */
  public function testMissingVariablePlugin() {
    $view_name = 'who_s_online';
    $warning_message = t('The %plugin plugin does not exist.', ['%plugin' => 'view:' . $view_name]);

    $edit = [
      'plugin_id' => 'view:' . $view_name,
      'name' => 'who_s_online',
    ];
    $this->drupalPostForm('admin/structure/snippet/alpha/edit/variable/add', $edit, 'Save and continue');

    $this->drupalGet("admin/structure/snippet/alpha/edit");

    // Removing the view would cause removing the whole snippet. So we just
    // disable and check it the snippet will react on this correctly.
    \Drupal::service('entity.manager')
      ->getStorage('view')
      ->load($view_name)
      ->disable()
      ->save();

    $this->drupalGet("admin/structure/snippet/alpha/edit/variable/$view_name/edit");
    $this->assertSession()->statusCodeEquals(404);
    $this->assertWarningMessage($warning_message);

    $this->drupalGet('admin/structure/snippet/alpha/edit/template');
    $this->assertWarningMessage($warning_message);
    $this->assertXpath(sprintf('//table//td[text() = "view:%s - missing"]', $view_name));

    // Make sure the variable still can be deleted.
    $this->drupalPostForm("admin/structure/snippet/alpha/edit/variable/$view_name/delete", [], 'Delete');
    $this->assertStatusMessage('The variable has been removed.');
  }

}
