<?php

namespace Drupal\Tests\element_class_formatter\Functional;

use Drupal\entity_test\Entity\EntityTest;

/**
 * Functional tests for the link list with class formatter.
 *
 * @group element_class_formatter
 */
class StringListClassFormatterTest extends ElementClassFormatterTestBase {

  const TEST_CLASS = 'test-string-list-class';

  /**
   * {@inheritdoc}
   */
  public function testClassFormatter() {
    $formatter_settings = [
      'class' => self::TEST_CLASS,
      'list_type' => 'ol',
    ];
    $field_config = $this->createEntityField('string_list_class', 'string', $formatter_settings);

    $entity = EntityTest::create([
      $field_config->getName() => [
        0 => ['value' => 'test string 1'],
        1 => ['value' => 'test string 2'],
      ],
    ]);
    $entity->save();

    $this->drupalGet($entity->toUrl());
    $assert_session = $this->assertSession();
    $assert_session->elementExists('css', 'ol.' . self::TEST_CLASS);
  }

}
