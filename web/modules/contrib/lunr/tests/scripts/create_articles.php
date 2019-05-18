<?php

/**
 * @file
 * Script for bulk creating articles.
 */

use Drupal\node\Entity\Node;

for ($i = 0; $i < 1000; ++$i) {
  $node = Node::create([
    'type' => 'article',
  ]);
  $node->title->generateSampleItems();
  $node->field_image->generateSampleItems();
  $node->body->generateSampleItems();
  $node->body->summary = '';
  $node->uid->generateSampleItems();
  $node->setPublished();
  $node->save();
}
