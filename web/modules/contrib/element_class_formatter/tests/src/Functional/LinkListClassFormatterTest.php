<?php

namespace Drupal\Tests\element_class_formatter\Functional;

use Drupal\entity_test\Entity\EntityTest;

/**
 * Functional tests for the link list with class formatter.
 *
 * @group element_class_formatter
 */
class LinkListClassFormatterTest extends ElementClassFormatterTestBase {

  const TEST_CLASS = 'test-link-list-class';

  /**
   * {@inheritdoc}
   */
  public function testClassFormatter() {
    $formatter_settings = [
      'class' => self::TEST_CLASS,
      'list_type' => 'ol',
    ];
    $field_config = $this->createEntityField('link_list_class', 'link', $formatter_settings);

    $entity = EntityTest::create([
      $field_config->getName() => [
        0 => ['uri' => 'https://drupal.org'],
        1 => ['uri' => 'https://example.com'],
      ],
    ]);
    $entity->save();

    $this->drupalGet($entity->toUrl());
    $assert_session = $this->assertSession();
    $assert_session->elementExists('css', 'ol.' . self::TEST_CLASS);
  }

}
