<?php

namespace Drupal\Tests\element_class_formatter\Functional;

use Drupal\entity_test\Entity\EntityTest;

/**
 * Functional tests for the link with class formatter.
 *
 * @group element_class_formatter
 */
class LinkClassFormatterTest extends ElementClassFormatterTestBase {

  const TEST_CLASS = 'test-link-class';

  /**
   * {@inheritdoc}
   */
  public function testClassFormatter() {
    $field_config = $this->createEntityField('link_class', 'link', ['class' => self::TEST_CLASS]);

    $entity = EntityTest::create([
      $field_config->getName() => [['uri' => 'https://drupal.org']],
    ]);
    $entity->save();

    $this->drupalGet($entity->toUrl());
    $assert_session = $this->assertSession();
    $assert_session->elementExists('css', 'a.' . self::TEST_CLASS);
  }

}
