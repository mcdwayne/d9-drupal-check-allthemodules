<?php

namespace Drupal\Tests\bibcite_bibtex\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\bibcite_bibtex\Encoder\BibtexEncoder;

/**
 * @coversDefaultClass \Drupal\bibcite_bibtex\Encoder\BibtexEncoder
 * @group bibcite
 */
class BibtexEncoderTest extends UnitTestCase {

  /**
   * @coversDefaultClass
   */
  public function testLineEndings() {
    $example1 = "unit1 \r\n field1 \r\n field2 \r\n\r\n unit2 \r\n field1 \r\n field2 \r\n\r\n";
    $expect1 = "unit1 \n field1 \n field2 \n\n unit2 \n field1 \n field2 \n\n";

    $example2 = "unit1 \r field1 \r field2 \r\r unit2 \r field1 \r field2 \r\r";
    $expect2 = "unit1 \n field1 \n field2 \n\n unit2 \n field1 \n field2 \n\n";

    $example3 = "unit1 \n field1 \n field2 \n\n unit2 \n field1 \n field2 \n\n";
    $expect3 = "unit1 \n field1 \n field2 \n\n unit2 \n field1 \n field2 \n\n";

    $encoder = new BibtexEncoder();
    $result1 = $encoder->lineEndingsReplace($example1);
    $result2 = $encoder->lineEndingsReplace($example2);
    $result3 = $encoder->lineEndingsReplace($example3);
    $this->assertEquals($expect1, $result1);
    $this->assertEquals($expect2, $result2);
    $this->assertEquals($expect3, $result3);
  }

}
