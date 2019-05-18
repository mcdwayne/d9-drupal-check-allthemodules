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
 * @coversDefaultClass \Drupal\forena\FrxPlugin\Document\HTML
 */
class HTMLTest extends FrxTestCase{
  /**
   * Test CSV formattting
   */
  private $html = '<p>Run boy Run!</p>';

  /**
   * Table function
   */
  public function testCSV() {
    $doc=DocManager::instance()->setDocument('html');
    $doc->header();
    $doc->write($this->html);
    $doc->footer();
    $doc->title = 'Test HTML';
    $report = $doc->flush();

    // Check the headers
    $this->assertContains("<p>Run boy Run!</p>", $report, 'Data Present');
    $this->assertContains("<title>Test HTML</title>", $report, "Title Present");
    $this->assertContains("<h1>Test HTML</h1>", $report, "Title Header Present");

    // Check headers
    $headers = $doc->headers;
    $this->assertArrayHasKey('Content-Type', $headers);
    $this->assertContains('text/html', $headers['Content-Type']);
  }
}