<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 3/21/2016
 * Time: 9:25 AM
 */

namespace Drupal\Tests\forena\Unit\Renderer;

use Drupal\forena\Report;
use Drupal\Tests\forena\Unit\FrxTestCase;

/**
 * @group Forena
 * @require module forena
 * @coversDefaultClass \Drupal\forena\FrxPlugin\Renderer\FrxXML
 */
class FrxCrosstabTest extends FrxRendererTestCase {
  // Test report.
  private $doc = '<?xml version="1.0"?>
      <!DOCTYPE root [
      <!ENTITY nbsp "&#160;">
      ]>
      <html xmlns:frx="urn:FrxReports">
      <head>
      <title>Report Title</title>
      <frx:category>Category</frx:category>
      <frx:fields>
      </frx:fields>
      </head>
      <body>
        <table frx:renderer="FrxCrosstab" frx:group="{state}" frx:dim="{gender}">
          <thead>
            <tr><th>State</th><td>users</td></tr>
          </thead>
          <tbody>
            <tr><th>{state}</th><td>{users}</td></tr>
          </tbody>
        </table>
      </body>
      </html>';


  /**
   * @param array $array
   * @return array
   * Helper function
   */
  public function arrayData(array $array) {
    $new_array = [];
    foreach ($array as $r => $row)  {
      $new_row = [];
      foreach ($row as $key => $value) {
        $new_row[$key] = $value['data'];
      }
      $new_array[] = $new_row;
    }
    return $new_array;
  }

  /**
   * Test Crosstab Renderer
   */
  public function testFrxCrosstab() {

    // Generate the crosstab
    $data = $this->dataManager()->data('test/crosstab_data');
    $this->pushData($data);
    $elements = $this->render('\Drupal\forena\FrxPlugin\Renderer\FrxCrosstab', $this->doc, 'table');
    $this->popData();

    // Assertions
    $this->assertGreaterThan(0, count($elements), $elements);
    $element = $elements[0];
    $this->assertEquals('table', $element['#type']);
    $rows = $element['#rows'];
    $this->assertEquals(2, count($rows), "Correct row grouping count returned");
    $data_rows = $this->arrayData($rows);
    $AL = $data_rows[0];
    $CA = $data_rows[1];
    $header_data[] = $element['#header'];
    $headers = $this->arrayData($header_data);
    $h = $headers[0];
    $this->assertEquals(4, count($h));
    $this->assertEquals(4, count($AL), "Correct Number of columns AL");
    $this->assertEquals(4, count($CA), "Correct number of columns CA");
    $this->assertEquals('State', $h[0]);
    $this->assertEquals('Male', $h[1]);
    $this->assertEquals('Female', $h[2]);
    $this->assertEquals('Unknown', $h[3]);

  }
}