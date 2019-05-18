<?php

namespace Drupal\Tests\element_class_formatter\Functional;

use Drupal\entity_test\Entity\EntityTest;

/**
 * Functional tests for the mailto link with class formatter.
 *
 * @group element_class_formatter
 */
class TelephoneLinkClassFormatterTest extends ElementClassFormatterTestBase {

  const TEST_CLASS = 'test-phone-class';

  /**
   * {@inheritdoc}
   */
  public function testClassFormatter() {
    $field_config = $this->createEntityField('telephone_link_class', 'telephone', ['class' => self::TEST_CLASS]);

    $entity = EntityTest::create([
      $field_config->getName() => [['value' => '1800888888']],
    ]);
    $entity->save();

    $this->drupalGet($entity->toUrl());
    $assert_session = $this->assertSession();
    $assert_session->elementExists('css', 'a.' . self::TEST_CLASS);
  }

}
