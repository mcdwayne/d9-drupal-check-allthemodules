<?php

namespace Drupal\Tests\media_entity_carto\Unit;

use Drupal\Core\Field\Plugin\Field\FieldType\StringLongItem;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\media_entity_carto\Plugin\Validation\Constraint\CartoEmbedCodeConstraint;
use Drupal\media_entity_carto\Plugin\Validation\Constraint\CartoEmbedCodeConstraintValidator;
use Drupal\Tests\UnitTestCase;

/**
 * Tests media_entity_carto constraints.
 *
 * @group media_entity
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
   * Tests cartoEmbedCode constraint.
   *
   * @covers \Drupal\media_entity_carto\Plugin\Validation\Constraint\CartoEmbedCodeConstraintValidator
   * @covers \Drupal\media_entity_carto\Plugin\Validation\Constraint\CartoEmbedCodeConstraint
   *
   * @dataProvider embedCodeProvider
   */
  public function testCartoEmbedCodeConstraint($embed_code, $expected_violation_count) {
    // Check message in constraint.
    $constraint = new CartoEmbedCodeConstraint();
    $this->assertEquals('Not valid CARTO Map URL/embed code.', $constraint->message, 'Correct constraint message found.');

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

    $validator = new CartoEmbedCodeConstraintValidator();
    $validator->initialize($execution_context);

    $validator->validate($this->getMockFieldItem($embed_code), $constraint);
  }

  /**
   * Provides test data for testCartoEmbedCodeConstraint().
   */
  public function embedCodeProvider() {
    return [
      'valid CARTO URL 1' => ['https://plopesc.carto.com/builder/dc610160-30b0-11e7-b058-0e233c30368f/embed', 0],
      'valid CARTO URL 2' => ['https://client.carto.com/u/username/viz/72159080-31f9-4509-a018-217a6d99c752/embed_map', 0],
      'valid CARTO URL 3' => ['https://client.carto.com/viz/72159080-31f9-4509-a018-217a6d99c752/embed_map', 0],
      'valid CARTO embed code' => ['<iframe width="100%" height="520" frameborder="0" src="https://plopesc.carto.com/builder/dc610160-30b0-11e7-b058-0e233c30368f/embed" allowfullscreen webkitallowfullscreen mozallowfullscreen oallowfullscreen msallowfullscreen></iframe>', 0],
      'invalid URL' => ['https://drupal.org/project/media_entity_carto', 1],
      'invalid text' => ['I want my Map!', 1],
    ];
  }

}
