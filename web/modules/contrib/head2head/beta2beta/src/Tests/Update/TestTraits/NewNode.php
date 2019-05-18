<?php

/**
 * @file
 * Contains \Drupal\beta2beta\Tests\Update\TestTraits\NewNode.
 */

namespace Drupal\beta2beta\Tests\Update\TestTraits;

/**
 * Provides a trait for testing the ability to create new content.
 */
trait NewNode {

  /**
   * Tests adding a new article.
   */
  public function testNewNode() {
    $this->runUpdates();

    $editor = $this->drupalCreateUser(['create article content', 'edit own article content']);
    $this->drupalLogin($editor);
    // Create a node.
    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName(8);
    $edit['body[0][value]'] = $this->randomMachineName(16);
    $this->drupalPostForm('node/add/article', $edit, t('Save'));

    // Check that the article has been created.
    $this->assertRaw(t('!post %title has been created.', ['!post' => 'Article', '%title' => $edit['title[0][value]']]), 'Basic page created.');

    // Check that the node exists in the database.
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->assertTrue($node, 'Node found in database.');

  }

}
