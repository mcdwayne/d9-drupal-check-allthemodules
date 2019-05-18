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
 * @coversDefaultClass \Drupal\forena\FrxPlugin\Renderer\FrxSource
 */
class FrxSourceTest extends FrxRendererTestCase {
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
        <div frx:renderer="FrxSource">
          <p frx:if="test"></p>
        </div>
      </body>
      </html>';

  /**
   * Test FrxSource Renderer
   */
  public function testFrxSource() {

    $output = $this->render('\Drupal\forena\FrxPlugin\Renderer\FrxSource', $this->doc);
    $this->assertContains(
      '&lt;p frx:if=&quot;test&quot;/&gt;',
      $output,
      'Rendered Control contains raw markup'
    );
  }
}