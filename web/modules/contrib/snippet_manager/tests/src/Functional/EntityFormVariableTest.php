<?php

namespace Drupal\Tests\snippet_manager\Functional;

use Drupal\user\Entity\Role;

/**
 * Entity form variable test.
 *
 * @group snippet_manager
 */
class EntityFormVariableTest extends TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'snippet_manager',
    'snippet_manager_test',
    'node',
    'file',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->createContentType(['name' => 'Page', 'type' => 'page']);

    $role = Role::load('authenticated');
    $this->grantPermissions($role, ['create page content']);

    // Displaying the snippet unconditionally may break other tests.
    \Drupal::state()->set('show_snippet', $this->snippetId);
  }

  /**
   * Test callback.
   */
  public function testEntityVariable() {
    $this->drupalGet($this->snippetEditUrl . '/variable/add');

    // This option should not exist as the file entity type has no form classes.
    $this->assertNoXpath('//select[@name = "plugin_id"]//option[@value = "entity_form:file"]');
    $edit = [
      'plugin_id' => 'entity_form:node',
      'name' => 'node_form',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save and continue');
    $this->assertStatusMessage('The variable has been created.');

    $edit = [
      'configuration[bundle]' => 'page',
      'configuration[form_mode]' => 'default',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertStatusMessage('The variable has been updated.');

    $edit = [
      'template[value]' => '<div class="snippet-node-form">{{ node_form }}</div>',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    $this->drupalGet('<front>');
    $this->assertXpath('//div[@class="snippet-node-form"]/form[@id = "node-page-form"]');
    $edit = [
      'title[0][value]' => 'Foo',
      'body[0][value]' => 'Foo',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertStatusMessage(t('Page %title has been created.', ['%title' => 'Foo']));
  }

}
