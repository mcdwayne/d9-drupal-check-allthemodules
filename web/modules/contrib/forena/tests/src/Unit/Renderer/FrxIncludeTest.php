<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 4/28/16
 * Time: 5:57 AM
 */

namespace Drupal\Tests\forena\Unit\Renderer;

/**
 * Class FrxIncludeTest
 * @group Forena
 * @require module forena
 * @coversDefaultClass \Drupal\forena\FrxPlugin\Renderer\FrxInclude
 */
class FrxIncludeTest extends FrxRendererTestCase {

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
        <frx:field id="testlink" link="mypage"/>
      </frx:fields>
      </head>
      <body>
        <h1>Header</h1>
        <div frx:renderer="FrxInclude" src="reports/sample">
          <p frx:if="test"></p>
        </div>
        <p>Footer</p>
      </body>
      </html>';

  /**
   * Test the rendering of the control.
   */
  public function testRender() {
    $doc = $this->getDocument();
    $doc->clear();
    $this->render('\Drupal\forena\FrxPlugin\Renderer\FrxInclude', $this->doc);
    $content = $doc->flush();
    $output = $content['report']['#template'];
    $this->assertContains('col1', $output, "Report content in buffer");


  }

}