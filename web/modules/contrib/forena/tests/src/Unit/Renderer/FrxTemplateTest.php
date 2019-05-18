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
class FrxTemplateTest extends FrxRendererTestCase {
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
        <div frx:renderer="FrxTemplate" >{salutation}</div>
      </body>
      </html>';

  /**
   * Test FrxXML Renderer
   */
  public function testFrxTemplate() {
    $data = $this->dataManager()->data('test/template_data');
    $this->pushData($data);
    $output = $this->render('\Drupal\forena\FrxPlugin\Renderer\FrxTemplate', $this->doc);
    $this->popData();
    $this->assertContains("Hello Mr. Dave",
      $output,
      'Template Rendered'
    );


  }
}