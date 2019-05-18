<?php

namespace Drupal\Tests\element_class_formatter\Functional;

use Drupal\entity_test\Entity\EntityTest;

/**
 * Functional tests for the mailto link with class formatter.
 *
 * @group element_class_formatter
 */
class EmailLinkClassFormatterTest extends ElementClassFormatterTestBase {

  const TEST_CLASS = 'test-mailto-class';

  /**
   * {@inheritdoc}
   */
  public function testClassFormatter() {
    $field_config = $this->createEntityField('email_link_class', 'email', ['class' => self::TEST_CLASS]);

    $entity = EntityTest::create([
      $field_config->getName() => [['value' => 'test@example.com']],
    ]);
    $entity->save();

    $this->drupalGet($entity->toUrl());
    $assert_session = $this->assertSession();
    $assert_session->elementExists('css', 'a.' . self::TEST_CLASS);
  }

}
