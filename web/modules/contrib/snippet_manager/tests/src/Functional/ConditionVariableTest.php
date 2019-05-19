<?php

namespace Drupal\Tests\snippet_manager\Functional;

/**
 * Condition variable test.
 *
 * @group snippet_manager
 */
class ConditionVariableTest extends TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'snippet_manager',
    'snippet_manager_test',
    'node',
  ];

  /**
   * {@inheritdoc}
   *
   * Configuration check fails on the first form step because condition plugin
   * ID is not specified yet.
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Test callback.
   */
  public function testConditionVariable() {

    \Drupal::state()->set('show_snippet', 'alpha');

    $edit = [
      'plugin_id' => 'condition:request_path',
      'name' => 'node_page',
    ];
    $this->drupalPostForm('admin/structure/snippet/alpha/edit/variable/add', $edit, 'Save and continue');
    $this->assertStatusMessage('The variable has been created.');

    $edit = [
      'configuration[condition][pages]' => '/node',
    ];
    $this->drupalPostForm('admin/structure/snippet/alpha/edit/variable/node_page/edit', $edit, 'Save');

    $edit = [
      'template[value]' => "{% if node_page %}Foo{% else %}Bar{% endif %}",
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    // @todo make it work with cache contexts.
    drupal_flush_all_caches();
    $this->drupalGet('node');
    $this->assertSession()->responseContains('Foo');
    $this->assertSession()->responseNotContains('Bar');

    drupal_flush_all_caches();
    $this->drupalGet('user');
    $this->assertSession()->responseContains('Bar');
    $this->assertSession()->responseNotContains('Foo');

    // Negate the condition.
    $edit = [
      'configuration[condition][negate]' => TRUE,
    ];
    $this->drupalPostForm('admin/structure/snippet/alpha/edit/variable/node_page/edit', $edit, 'Save');

    drupal_flush_all_caches();
    $this->drupalGet('node');
    $this->assertSession()->responseContains('Bar');
    $this->assertSession()->responseNotContains('Foo');

    drupal_flush_all_caches();
    $this->drupalGet('user');
    $this->assertSession()->responseContains('Foo');
    $this->assertSession()->responseNotContains('Bar');
  }

}
