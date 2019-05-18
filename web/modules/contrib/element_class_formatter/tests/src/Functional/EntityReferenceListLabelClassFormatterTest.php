<?php

namespace Drupal\Tests\element_class_formatter\Functional;

use Drupal\entity_test\Entity\EntityTest;

/**
 * Functional tests for the entity reference list label with class formatter.
 *
 * @group element_class_formatter
 */
class EntityReferenceListLabelClassFormatterTest extends ElementClassFormatterTestBase {

  const TEST_CLASS = 'test-entity-ref-list-class';

  /**
   * {@inheritdoc}
   */
  public function testClassFormatter() {
    $formatter_settings = [
      'class' => self::TEST_CLASS,
      'link' => TRUE,
      'list_type' => 'ol',
    ];
    $field_config = $this->createEntityField('entity_reference_list_label_class', 'entity_reference', $formatter_settings);
    $referenced_node = $this->drupalCreateNode(['type' => 'referenced_content']);

    $entity = EntityTest::create([
      $field_config->getName() => [
        0 => ['target_id' => $referenced_node->id()],
        1 => ['target_id' => $referenced_node->id()],
      ],
    ]);
    $entity->save();

    $this->drupalGet($entity->toUrl());
    $assert_session = $this->assertSession();
    $assert_session->elementExists('css', 'ol.' . self::TEST_CLASS);
  }

}
