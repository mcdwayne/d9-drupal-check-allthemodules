<?php

namespace Drupal\Tests\media_entity_dreambroker\Unit;

use Drupal\Core\Field\Plugin\Field\FieldType\StringLongItem;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\media_entity_dreambroker\Plugin\Validation\Constraint\DreambrokerEmbedCodeConstraint;
use Drupal\media_entity_dreambroker\Plugin\Validation\Constraint\DreambrokerEmbedCodeConstraintValidator;
use Drupal\Tests\UnitTestCase;

/**
 * Tests media_entity_dreambroker constraints.
 *
 * @group media_entity_dreambroker
 */
class ConstraintsTest extends UnitTestCase {

  /**
   * Creates a string_long FieldItemInterface wrapper around a value.
   *
   * @param string $value
   *   The wrapped value.
   *
   * @return \Drupal\Core\Field\FieldItemInterface
   *   Mocked string field item.
   */
  protected function getMockFieldItem($value) {
    $definition = $this->prophesize(ComplexDataDefinitionInterface::class);
    $definition->getPropertyDefinitions()->willReturn([]);

    $item = new StringLongItem($definition->reveal());
    $item->set('value', $value);

    return $item;
  }

  /**
   * Tests DreambrokerEmbedCode constraint.
   *
   * @covers \Drupal\media_entity_dreambroker\Plugin\Validation\Constraint\DreambrokerEmbedCodeConstraintValidator
   * @covers \Drupal\media_entity_dreambroker\Plugin\Validation\Constraint\DreambrokerEmbedCodeConstraint
   *
   * @dataProvider embedCodeProvider
   */
  public function testDreambrokerEmbedCodeConstraint($embed_code, $expected_violation_count) {
    // Check message in constraint.
    $constraint = new DreambrokerEmbedCodeConstraint();
    $this->assertEquals('Not valid Dream Broker URL/Embed code.', $constraint->message, 'Correct constraint message found.');

    $execution_context = $this->getMockBuilder('\Drupal\Core\TypedData\Validation\ExecutionContext')
      ->disableOriginalConstructor()
      ->getMock();

    if ($expected_violation_count) {
      $execution_context->expects($this->exactly($expected_violation_count))
        ->method('addViolation')
        ->with($constraint->message);
    }
    else {
      $execution_context->expects($this->exactly($expected_violation_count))
        ->method('addViolation');
    }

    $validator = new DreambrokerEmbedCodeConstraintValidator();
    $validator->initialize($execution_context);

    $validator->validate($this->getMockFieldItem($embed_code), $constraint);
  }

  /**
   * Provides test data for testDreambrokerEmbedCodeConstraint().
   */
  public function embedCodeProvider() {
    return [
      'valid dreambroker URL' => ['https://www.dreambroker.com/channel/1zcdkjfg/h8q6cakv', 0],
      'invalid URL' => ['https://drupal.org/project/media_entity_dreambroker', 1],
      'invalid text' => ['I want my Dream Broker video!', 1],
      'invalid dreambroker URL' => ['https://www.dreambroker.com/channelinvalid/1zcdkjfg/h8q6cakv', 1],
      'invalid dreambroker channel ID length' => ['https://www.dreambroker.com/channel/1zcdkjfgaaa/h8q6cakv', 1],
      'invalid dreambroker channel ID char' => ['https://www.dreambroker.com/channel/1zcdkjf#/h8q6cakv', 1],
      'invalid dreambroker video ID' => ['https://www.dreambroker.com/channel/1zcdkjfg/h8q6cak#', 1],
    ];
  }

}
