<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 4/11/16
 * Time: 9:32 PM
 */

namespace Drupal\Tests\forena\Unit;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\forena\Controller\ForenaController;

/**
 * Controller test
 * @group Forena
 * @require module forena
 * @coversDefaultClass \Drupal\forena\Controller\ForenaController
 */
class ForenaControllerTest extends FrxTestCase {

  /**
   * Test the basic page controller
   */
  public function testPageController() {
    $controller = new ForenaController();

    $report = $controller->report('test');
    $this->assertArrayHasKey('report', $report);
  }

  /**
   * Test Ajax return functions
   */
  public function testAjaxController() {
    $controller = new ForenaController();

    $report = $controller->ajaxReport('test', 'nojs');

    // Verify the nojs case. 
    $this->assertArrayHasKey('report', $report);


  }
}