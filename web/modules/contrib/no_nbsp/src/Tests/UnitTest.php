<?php

namespace Drupal\no_nbsp\Tests;

use Drupal\simpletest\KernelTestBase;

/**
 * Run unit tests on some functions.
 *
 * @group no_nbsp
 */
class UnitTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['no_nbsp'];

  /**
   * Test the function _no_nbsp_eraser.
   */
  public function testFunctionNoNbspEraser() {
    $this->assertEqual(_no_nbsp_eraser('l&nbsp;o&nbsp;l'), 'l o l');
    $this->assertEqual(_no_nbsp_eraser('l&nbsp;&nbsp;o&nbsp;&nbsp;l'), 'l o l');
    $this->assertEqual(_no_nbsp_eraser('l&nbsp; o&nbsp; l'), 'l o l');
    $this->assertEqual(_no_nbsp_eraser('l &nbsp; o &nbsp; l'), 'l o l');
    $this->assertEqual(_no_nbsp_eraser('l &nbsp;o &nbsp;l'), 'l o l');
    $this->assertEqual(_no_nbsp_eraser('l  o  l'), 'l o l');
    $this->assertEqual(_no_nbsp_eraser('l o l'), 'l o l');
    $this->assertEqual(_no_nbsp_eraser('l&nbspol'), 'l&nbspol');
    $this->assertEqual(_no_nbsp_eraser(' '), ' ');
    $this->assertEqual(_no_nbsp_eraser('&nbsp;'), ' ');
    $this->assertEqual(_no_nbsp_eraser('&nbsp;&nbsp;&nbsp;'), ' ');
    $this->assertEqual(_no_nbsp_eraser('&NBSP;'), ' ');
  }

}
