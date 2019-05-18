<?php

namespace Drupal\Tests\element_class_formatter\Functional;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\file\Entity\File;

/**
 * Functional tests for the file link with class formatter.
 *
 * @group element_class_formatter
 */
class FileLinkClassFormatterTest extends ElementClassFormatterTestBase {

  const TEST_CLASS = 'test-file-class';

  /**
   * {@inheritdoc}
   */
  public function testClassFormatter() {
    $formatter_settings = [
      'class' => self::TEST_CLASS,
    ];
    $field_config = $this->createEntityField('file_link_class', 'file', $formatter_settings);

    file_put_contents('public://file.txt', str_repeat('t', 10));
    $file = File::create([
      'uri' => 'public://file.txt',
      'filename' => 'file.txt',
    ]);
    $file->save();

    $entity = EntityTest::create([
      $field_config->getName() => [['target_id' => $file->id()]],
    ]);
    $entity->save();

    $this->drupalGet($entity->toUrl());
    $assert_session = $this->assertSession();
    $assert_session->elementExists('css', 'a.' . self::TEST_CLASS);
  }

}
