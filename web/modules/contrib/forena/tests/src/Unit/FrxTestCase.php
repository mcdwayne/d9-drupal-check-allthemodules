<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 2/6/16
 * Time: 4:02 PM
 */

namespace Drupal\Tests\forena\Unit;


use Drupal\Core\Form\FormState;
use Drupal\forena\FrxAPI;
use Drupal\Tests\forena\Unit\Mock\TestingAppService;
use Drupal\Tests\forena\Unit\Mock\TestingDataManager;
use Drupal\Tests\forena\Unit\Mock\TestingReportFileSystem;
use Drupal\Tests\UnitTestCase;

class FrxTestCase extends UnitTestCase {
  use FrxAPI;



  /**
   * Mock object instantiation.
   */
  public function setUp() {

    // Instantiate Mock Object. Order is important here.
    /** @var TestingAppService $app */
    $app = TestingAppService::instance(TRUE);
    TestingDataManager::instance(TRUE);
    TestingReportFileSystem::instance(TRUE);
    $app->form_state = new FormState();
    $app->parameterForm = $this->getMockBuilder('\Drupal\forena\Form\ParameterForm')
      ->setMethods(['t'])
      ->getMock();
    $app->parameterForm->method('t')->will($this->returnArgument(0));
  }

  public function initParametersForm() {
  }
}