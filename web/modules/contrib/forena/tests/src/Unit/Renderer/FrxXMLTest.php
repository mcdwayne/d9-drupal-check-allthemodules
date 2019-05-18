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
class FrxXMLTest extends FrxRendererTestCase {
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
        <div renderer="FrxXML"></div>
      </body>
      </html>';

  /**
   * Test FrxXML Renderer
   */
  public function testFrxXML() {
    $data = $this->dataManager()->data('test/simple_data');
    $this->pushData($data);
    $output = $this->render('\Drupal\forena\FrxPlugin\Renderer\FrxXML', $this->doc);
    $this->popData();
    $this->assertContains(
      '&lt;col1&gt;data&lt;/col1&gt;',
      $output,
      'Rendered Control contains column data'
    );
  }
}