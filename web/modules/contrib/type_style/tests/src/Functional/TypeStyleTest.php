<?php

namespace Drupal\Tests\type_style\Functional;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;

/**
 * Contains tests for the Type Style module.
 *
 * @group type_style
 */
class TypeStyleTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['type_style', 'node'];

  /**
   * Tests that the Type Style module works as expected.
   */
  public function testTypeStyle() {
    $user = $this->createUser(['administer content types', 'access content']);
    $this->drupalLogin($user);

    // Create a content type.
    $node_type = NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ]);
    $node_type->save();

    // Configure the icon and color style values.
    $edit = [
      'type_style[color]' => '#ffffff',
      'type_style[icon]' => 'my-icon',
    ];
    $this->drupalPostForm('/admin/structure/types/manage/article', $edit, t('Save content type'));
    // Ensure that the configuration form contains the correct settings.
    $this->drupalGet('/admin/structure/types/manage/article');
    $this->assertSession()->pageTextContains('Style settings');
    $this->assertSession()->responseContains('#ffffff');
    $this->assertSession()->responseContains('my-icon');

    // Test that we can get styles for a node of this type.
    $node = Node::create([
      'type' => 'article',
      'title' => 'Test',
    ]);
    $node->save();
    $this->assertEquals(type_style_get_style($node, 'color'), '#ffffff');
    $this->assertEquals(type_style_get_style($node, 'icon'), 'my-icon');

    // Test token replacement.
    $tests = [
      '[node:type-style-icon]' => 'my-icon',
      '[node:type-style-color]' => '#ffffff',
      '[node:type-style-bogus]' => '[node:type-style-bogus]',
    ];
    foreach ($tests as $input => $output) {
      $replaced = \Drupal::token()->replace($input, ['node' => $node]);
      $this->assertEquals($replaced, $output);
    }
  }

}
