<?php

namespace Drupal\Tests\element_class_formatter\Functional;

use Drupal\entity_test\Entity\EntityTest;

/**
 * Functional tests for the entity reference label with class formatter.
 *
 * @group element_class_formatter
 */
class EntityReferenceLabelClassFormatterTest extends ElementClassFormatterTestBase {

  const TEST_CLASS = 'test-entity-ref-class';

  /**
   * {@inheritdoc}
   */
  public function testClassFormatter() {
    $formatter_settings = [
      'class' => self::TEST_CLASS,
      'tag' => 'div',
    ];
    $field_config = $this->createEntityField('entity_reference_label_class', 'entity_reference', $formatter_settings);
    $referenced_node = $this->drupalCreateNode(['type' => 'referenced_content']);

    $entity = EntityTest::create([
      $field_config->getName() => [['target_id' => $referenced_node->id()]],
    ]);
    $entity->save();

    $this->drupalGet($entity->toUrl());
    $assert_session = $this->assertSession();
    $assert_session->elementExists('css', 'a.' . self::TEST_CLASS);
  }

}
