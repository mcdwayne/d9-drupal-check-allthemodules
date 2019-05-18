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
 * Base test class for renderer tests.
 */
abstract class FrxRendererTestCase extends FrxTestCase {

  /** @var  \Drupal\forena\Report */
  protected $report;
  /** @var  \Drupal\forena\FrxPlugin\Renderer\RendererInterface */
  protected $renderer;
  /**
   * @param string $class
   *   Renderer to test
   * @param $report
   *   Report text to test with.
   * @return string 
   *   Outuput of rendered control. 
   */
  public function render($class, $report, $tag='div') {
    $this->report = $r = new Report($report);
    /** @var \Drupal\forena\FrxPlugin\Renderer\RendererBase $object */
    $object = new $class($r);
    $dom = $r->dom;
    $div = $dom->getElementsByTagName($tag)->item(0);
    // Render the rport.
    $object->initReportNode($div);
    $this->renderer = $object;
    return $object->render();
  }

}