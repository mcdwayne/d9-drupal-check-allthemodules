<?php

namespace Drupal\Tests\snippet_manager\Functional;

use Drupal\node\Entity\Node;
use Drupal\user\Entity\Role;

/**
 * Entity variable test.
 *
 * @group snippet_manager
 */
class EntityVariableTest extends TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'snippet_manager',
    'snippet_manager_test',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->createContentType(['type' => 'page']);
    $this->drupalCreateNode(['title' => 'Foo', 'body' => 'foo_content']);
    $this->drupalCreateNode(['title' => 'Bar', 'body' => 'bar_content']);

    $role = Role::load('authenticated');
    $this->grantPermissions($role, ['edit any page content']);

    // Displaying the snippet unconditionally may break other tests.
    \Drupal::state()->set('show_snippet', $this->snippetId);
  }

  /**
   * Test callback.
   */
  public function testEntityVariable() {

    $edit_variable_url = $this->snippetEditUrl . '/variable/node/edit';

    $edit = [
      'plugin_id' => 'entity:node',
      'name' => 'node',
    ];
    $this->drupalPostForm($this->snippetEditUrl . '/variable/add', $edit, 'Save and continue');
    $this->assertStatusMessage('The variable has been created.');

    $edit = [
      'configuration[entity_id]' => 'Bar',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertStatusMessage('The variable has been updated.');
    $this->assertXpath('//main//table/tbody/tr/td[position() = 1]/a[@href="#snippet-edit-form" and text() = "node"]');

    $edit = [
      'template[value]' => '<div class="snippet-node">{{ node }}</div>',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    // -- Check specified node in the snippet.
    $this->drupalGet('node/1');
    $this->assertSnippetNode('Bar');

    $this->drupalGet('<front>');
    $this->assertSnippetNode('Bar');

    // -- Check entity access.
    $node = Node::load(2);

    // Unpublish the node an make sure it disappears from the snippet.
    $node->setUnpublished()->save();
    $this->drupalGet('<front>');
    $this->assertSession()->responseNotContains('Bar');

    // Enable bypass access option.
    $edit = [
      'configuration[bypass_access]' => TRUE,
    ];
    // We need to publish the node temporarily because entity reference widget
    // would not allow to submit the form.
    $node->setPublished()->save();
    $this->drupalPostForm($edit_variable_url, $edit, 'Save');
    $node->setUnpublished()->save();

    // Check whether the node returns back to the snippet.
    $this->drupalGet('<front>');
    $this->assertSnippetNode('Bar');
    $node->setPublished()->save();

    // -- Empty entity ID forces loading the node form the route.
    $edit = [
      'configuration[entity_id]' => '',
    ];
    $this->drupalPostForm($edit_variable_url, $edit, 'Save');

    $this->drupalGet('node/1');
    $this->assertSnippetNode('Foo');

    $this->drupalGet('node/2');
    $this->assertSnippetNode('Bar');

    $this->drupalGet('<front>');
    $this->assertNoXpath('//div[@class="snippet-node"]/article');

    // Test view mode option.
    $this->assertNoXpath('//a[text()="Read more"]');
    $edit = [
      'configuration[entity_id]' => 'Bar',
    ];
    $this->drupalPostForm($edit_variable_url, $edit, 'Save');
    $edit = [
      'configuration[entity_id]' => 'Bar',
      'configuration[view_mode]' => 'teaser',
    ];
    $this->drupalPostForm($edit_variable_url, $edit, 'Save');
    $this->assertXpath('//a[text()="Read more"]');

    // -- Remove the node and make sure it does not cause any exceptions.
    $node = $this->getNodeByTitle('Bar');
    $node->delete();
    $this->drupalGet('<front>');

    // -- Check operation link.
    $edit = [
      'configuration[entity_id]' => 'Foo',
    ];
    $this->drupalPostForm($edit_variable_url, $edit, 'Save');
    $this->click('//a[text()="Edit content"]');
    $this->assertPageTitle('<em>Edit page</em> Foo');

    // -- Check "Fields" option.
    $edit = [
      'configuration[entity_id]' => 'Foo',
      'configuration[render_mode]' => 'fields',
    ];
    $this->drupalPostForm($edit_variable_url, $edit, 'Save');
    $edit = [
      'template[value]' => '<div class="snippet-node">{{ node.body }}</div>',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Only node body should appear now.
    $this->assertNoXpath('//*[text()="Foo"]');
    $this->assertXpath('//p[text()="foo_content"]');
  }

  /**
   * Asserts that given node title presents in a snippet.
   *
   * @param string $expected_title
   *   The title of the node to check.
   */
  protected function assertSnippetNode($expected_title) {
    // Snippet output is cached by some reason.
    \Drupal::service('cache_tags.invalidator')->invalidateTags(['rendered']);
    $this->assertXpath(sprintf('//div[@class="snippet-node"]/article/h2/a/span[text() = "%s"]', $expected_title));
  }

}
