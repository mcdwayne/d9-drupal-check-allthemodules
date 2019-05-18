<?php

use Drupal\convert_media_tags_to_markup\Plugin\Filter\ConvertLegacyMediaTagsToMarkup;
use PHPUnit\Framework\TestCase;

/**
 * Test ConvertLegacyMediaTagsToMarkup.
 *
 * @group myproject
 */
class ConvertLegacyMediaTagsToMarkupTest extends TestCase {

  /**
   * Smoke test.
   */
  public function testSmoke() {
    $object = $this->getMockBuilder(ConvertLegacyMediaTagsToMarkup::class)
      // NULL = no methods are mocked; otherwise list the methods here.
      ->setMethods(NULL)
      ->disableOriginalConstructor()
      ->getMock();

    $this->assertTrue(is_object($object));
  }

}
