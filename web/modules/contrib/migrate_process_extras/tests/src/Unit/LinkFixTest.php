<?php

namespace Drupal\Tests\migrate_process_extras\Unit;

use Drupal\migrate_process_extras\Plugin\migrate\process\LinkFix;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * Test the link fix process plugin.
 *
 * @group migrate_process_extras
 */
class LinkFixTest extends MigrateProcessTestCase {

  /**
   * Test the php function is applied.
   *
   * @dataProvider linkDataProvider
   */
  public function testTransform($expected_link, $invalid_link) {
    $plugin = new LinkFix([], 'link_fix', []);

    // Test with one argument.
    $this->assertEquals($expected_link, $plugin->transform($invalid_link, $this->migrateExecutable, $this->row, 'destinationproperty'));
  }

  /**
   * Data provider.
   */
  public function linkDataProvider() {
    return [
      'http is added' => ['http://example.com', 'example.com'],
      'http is added v2' => ['http://www.example.com', 'www.example.com'],
      'Valid link ignored' => ['http://example.com', 'http://example.com'],
      'Valid link ignored v2' => ['http://www.example.com', 'http://www.example.com'],
      'Valid link ignored v3' => ['https://example.com', 'https://example.com'],
      'Valid link ignored v4' => ['https://www.example.com', 'https://www.example.com'],
    ];
  }

}
