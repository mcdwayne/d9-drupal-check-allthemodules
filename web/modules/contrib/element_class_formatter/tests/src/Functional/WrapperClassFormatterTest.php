<?php

namespace Drupal\Tests\element_class_formatter\Functional;

use Drupal\entity_test\Entity\EntityTest;

/**
 * Functional tests for the mailto link with class formatter.
 *
 * @group element_class_formatter
 */
class WrapperClassFormatterTest extends ElementClassFormatterTestBase {

  const TEST_CLASS = 'test-wrapper-class';

  /**
   * {@inheritdoc}
   */
  public function testClassFormatter() {
    $formatter_settings = [
      'class' => self::TEST_CLASS,
      'tag' => 'h2',
    ];
    $field_config = $this->createEntityField('wrapper_class', 'string', $formatter_settings);

    $entity = EntityTest::create([
      $field_config->getName() => [['value' => 'I am a string']],
    ]);
    $entity->save();

    $this->drupalGet($entity->toUrl());
    $assert_session = $this->assertSession();
    $assert_session->elementExists('css', 'h2.' . self::TEST_CLASS);
  }

}
