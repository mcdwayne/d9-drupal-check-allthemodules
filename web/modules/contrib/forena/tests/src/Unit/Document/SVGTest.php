<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 3/23/2016
 * Time: 8:08 AM
 */

namespace Drupal\Tests\forena\Unit\Document;


use Drupal\forena\DocManager;
use Drupal\Tests\forena\Unit\FrxTestCase;

/**
 * Test XML
 * @group Forena
 * @require module forena
 * @coversDefaultClass \Drupal\forena\FrxPlugin\Document\SVG
 */
class SVGTest extends FrxTestCase{
  /**
   * Test SVG document
   */
  private $svg = '<div><svg xmlns:xlink="http://www.w3.org/1999/xlink" height="100" width="100">
    <circle cx="50" cy="50" r="40" stroke="black" stroke-width="3" fill="red" />
    <a xlink:href="http://www.w3schools.com/svg/" target="_blank">
    <text x="0" y="15" fill="red">I love SVG!</text>
    </a>
    </svg></div>';

  /**
   * Table function
   */
  public function testSVG() {
    $doc=DocManager::instance()->setDocument('svg');
    $doc->header();
    $doc->write($this->svg);
    $doc->footer();
    $report = $doc->flush();

    // Check the headers
    $this->assertContains('<circle cx="50" cy="50" r="40"', $report, 'Data Present');
    $this->assertContains('<a xlink:href', $report, 'xlink namespace present');


    // Check headers
    $headers = $doc->headers;
    $this->assertArrayHasKey('Content-Type', $headers);
    $this->assertContains('image/svg+xml', $headers['Content-Type']);
  }
}