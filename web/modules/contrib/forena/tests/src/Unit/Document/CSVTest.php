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
 * Test CSV
 * @group Forena
 * @require module forena
 * @coversDefaultClass \Drupal\forena\FrxPlugin\Document\CSV
 */
class CSVTest extends FrxTestCase{
  /**
   * Test CSV formattting
   */
  private $table = '
  <div>
    <table>
      <thead>
        <tr>
          <th>col_1</th>
          <th>col_2</th>
        </tr>
      </thead>
      <tbody>
        <tr >
          <td>1</td>
          <td>2</td>
        </tr>
        <tr>
          <td>text, with, commas</td>
          <td>text without commas</td>
        </tr>
      </tbody>
    </table>
  </div>';

  /**
   * Table function
   */
  public function testCSV() {
    $doc=DocManager::instance()->setDocument('csv');
    $doc->header();
    $doc->write($this->table);
    $doc->footer();
    $report = $doc->flush();

    // Check the headers
    $this->assertContains("col_1,col_2", $report, 'Column Headers present');
    $this->assertContains("1,2", $report, "Simple numbers present");
    $this->assertContains('"text, with, commas",text without commas', $report);

    // Check headers
    $headers = $doc->headers;
    $this->assertArrayHasKey('Content-Type', $headers);
    $this->assertContains('application/csv', $headers['Content-Type']);
  }
}