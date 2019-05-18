<?php

namespace Drupal\Tests\sendwithus\Unit;

use Drupal\sendwithus\Context;
use Drupal\sendwithus\Resolver\Variable\VariableCollector;
use Drupal\sendwithus\Resolver\Variable\VariableCollectorInterface;
use Drupal\sendwithus\Template;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * VariableCollector unit tests.
 *
 * @group sendwithus
 * @coversDefaultClass \Drupal\sendwithus\Resolver\Variable\VariableCollector
 */
class VariableCollectorTest extends UnitTestCase {

  /**
   * @covers ::__construct
   * @covers ::addCollector
   * @covers ::collect
   * @covers ::getCollectors
   */
  public function testDefault() {
    // Make sure we can't enter invalid resolvers.
    $exception = FALSE;
    try {
      new VariableCollector([1]);
    }
    catch (\InvalidArgumentException $e) {
      $exception = TRUE;
    }
    $this->assertTrue($exception);

    $stub1 = $this->createMock(VariableCollectorInterface::class);
    $stub2 = $this->createMock(VariableCollectorInterface::class);

    $sut = new VariableCollector();
    $sut->addCollector($stub1);
    $this->assertEquals(1, count($sut->getCollectors()));

    $sut = new VariableCollector([$stub1, $stub2]);
    $this->assertEquals(2, count($sut->getCollectors()));

    $context = new Context('module', 'id', new ParameterBag(['data' => 123]));
    $template = new Template('12345');
    $sut->collect($template, $context);

    // Make sure we get empty variables.
    $this->assertEquals(new ParameterBag([]), $template->getVariables());

    $stub2->method('collect')
      ->willReturnCallback(function (Template $template) {
        $template->setVariable('test', 123);
      });
    $sut->collect($template, $context);

    $this->assertEquals(new ParameterBag(['test' => 123]), $template->getVariables());

    // Make sure adding more variables (in different collector) will populate
    // the template variables.
    $stub1->method('collect')
      ->willReturnCallback(function (Template $template) {
        $template->setVariable('variable_value', 'value');
      });

    $sut->collect($template, $context);

    $this->assertEquals(new ParameterBag(['test' => 123, 'variable_value' => 'value']), $template->getVariables());
  }

}
