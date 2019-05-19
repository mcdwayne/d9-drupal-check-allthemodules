<?php

namespace Drupal\Tests\trash\Functional;

use Drupal\block_content\BlockContentTypeInterface;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeTypeInterface;

/**
 *
 */
trait TrashTestTrait {

  /**
   * @param string $name
   *
   * @return \Drupal\node\NodeTypeInterface
   */
  public function createNodeType($name) {
    $node_type = NodeType::create([
      'type' => $name,
      'label' => $name,
    ]);
    $node_type->save();
    $this->assertTrue($node_type instanceof NodeTypeInterface);
    return $node_type;
  }

  /**
   * @param \Drupal\node\NodeTypeInterface $node_type
   * @param string $title
   * @param bool $moderated
   */
  public function createContent(NodeTypeInterface $node_type, $title, $moderated = TRUE) {
    $session = $this->getSession();
    $this->drupalGet('/node/add/' . $node_type->id());
    $this->assertSession()->statusCodeEquals(200);
    $page = $session->getPage();
    $page->fillField('Title', $title);
    if ($moderated) {
      $page->findButton('Save and Publish')->click();
    }
    else {
      $page->findButton(t('Save'))->click();
    }
    $this->assertTrue($session->getPage()->hasContent("{$title} has been created"));
  }

  /**
   * @param string $entity_type_id
   * @param string $title
   * @param string $moderation_state
   */
  public function assertModerationState($entity_type_id, $title, $moderation_state) {
    $storage = \Drupal::entityTypeManager()->getStorage($entity_type_id);
    $label = $storage->getEntityType()->getKey('label');
    $entities = $storage->loadByProperties([$label => $title]);
    $entity = current($entities);
    $this->assertTrue($entity->moderation_state->target_id == $moderation_state);
  }

  /**
   * @param string $name
   *
   * @return \Drupal\block_content\BlockContentTypeInterface
   */
  public function createBlockContentType($name) {
    $block_type = BlockContentType::create([
      'id' => $name,
      'label' => $name,
    ]);
    $block_type->save();
    $this->assertTrue($block_type instanceof BlockContentTypeInterface);
    return $block_type;
  }

  /**
   * Create Block.
   */
  public function createBlock(BlockContentTypeInterface $block_type, $title, $moderated = TRUE) {
    $session = $this->getSession();
    $this->drupalGet('/block/add/' . $block_type->id());
    $this->assertSession()->statusCodeEquals(200);
    $page = $session->getPage();
    $page->fillField('Block description', $title);
    if ($moderated) {
      $page->findButton('Save and Publish')->click();
    }
    else {
      $page->findButton(t('Save'))->click();
    }
    $this->assertTrue($session->getPage()->hasContent("{$block_type->id()} {$title} has been created"));
  }

}
