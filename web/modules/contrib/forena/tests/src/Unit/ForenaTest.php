<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 3/13/2017
 * Time: 9:54 AM
 */

namespace Drupal\Tests\forena\Unit;


use Drupal\forena\Forena;

/**
 * Class ForenaTest
 * @group Forena
 */
class ForenaTest extends FrxTestCase {

  /** @var  Forena */
  protected $forena;

  public function setUp() {

    $this->forena = new Forena();
  }

  public function testReport() {
    $content = $this->forena->report('test');
    $this->assertNotEmpty($content);
    $content2 = $this->forena->report('include');
    $this->assertNotEmpty($content2);
    $this->assertNotEquals($content['report']['#template'], $content2['report']['#template']);
  }
}