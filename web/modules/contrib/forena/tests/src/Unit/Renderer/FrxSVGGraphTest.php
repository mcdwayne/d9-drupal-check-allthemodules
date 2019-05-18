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
 * Graph test.
 * @group Forena
 * @require module forena
 * @coversDefaultClass \Drupal\forena\FrxPlugin\Renderer\FrxSVGGraph
 */
class FrxSVGGraphTest extends FrxRendererTestCase {
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
        <svg frx:renderer="FrxSVGGraph" frx:type="PieGraph"
           frx:series="{users}" frx:label="{state} - {gender}">
        </svg>
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
   * SVGGraph
   */
  public function testFrxSvgGraph() {

    // Generate the crosstab
    $data = $this->dataManager()->data('test/crosstab_data');
    $this->pushData($data);
    $elements = $this->render('\Drupal\forena\FrxPlugin\Renderer\FrxSVGGraph', $this->doc, 'svg');
    $this->popData();

    /** @var \Drupal\forena\FrxPlugin\Renderer\FrxSVGGraph $r */
    $r = $this->renderer;

    // Assertions
    $this->assertEquals(5, count($r->graphData));
    $this->assertEquals('8081', $r->graphData[0]['users']);

  }
}