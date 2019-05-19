<?php

namespace Drupal\Tests\xero\Unit\Plugin\DataType;

use Drupal\Tests\UnitTestCase;
use Drupal\xero\Plugin\DataType\JournalLine;

/**
 * Test the xero_journal_line type.
 *
 * @coversDefaultClass \Drupal\xero\Plugin\DataType\JournalLine
 * @group Xero
 */
class JournalLineTest extends UnitTestCase {

  /**
   * Assert that static variables are present.
   */
  public function testStaticVariables() {
    $this->assertEquals('JournalLine', JournalLine::$xero_name);
    $this->assertEquals('JournalLines', JournalLine::$plural_name);
  }

}
