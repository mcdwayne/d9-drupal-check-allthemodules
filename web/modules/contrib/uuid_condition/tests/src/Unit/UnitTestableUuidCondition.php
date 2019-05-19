<?php

namespace Drupal\Tests\uuid_condition\Unit;

use Drupal\uuid_condition\Plugin\Condition\UuidCondition;
use Drupal\node\Entity\Node;

/**
 * Class UnitTestableUuidCondition.
 *
 * @package Drupal\Tests\uuid_condition\Unit
 */
class UnitTestableUuidCondition extends UuidCondition {

  /**
   * Mocked node.
   *
   * @var \Drupal\node\Entity\Node|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $mockNode;

  /**
   * Overwrite the constructor as we are not testing Drupal's plugin system.
   */
  public function __construct() {
    $this->mockNode = NULL;
  }

  /**
   * Setter for $mockNode.
   *
   * @param \Drupal\node\Entity\Node $mock_node
   *   The mock node.
   */
  public function setMockContextNode(Node $mock_node) {
    $this->mockNode = $mock_node;
  }

  /**
   * Overrides ContextAware getContextValue() to return various mocked contexts.
   *
   * @param string $context
   *   The context to get.
   *
   * @return NULL|\Drupal\node\Entity\Node
   *   The node if it exists, else NULL.
   */
  public function getContextValue($context) {
    switch ($context) {
      case 'node':
        if ($this->mockNode) {
          return $this->mockNode;
        }
        break;
    }
    return NULL;
  }

}
