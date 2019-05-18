<?php

namespace Drupal\Tests\sendwithus\Unit;

use Drupal\sendwithus\Context;
use Drupal\sendwithus\Resolver\Template\TemplateResolver;
use Drupal\sendwithus\Resolver\Template\TemplateResolverInterface;
use Drupal\sendwithus\Template;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * TemplateResolver unit tests.
 *
 * @group sendwithus
 * @coversDefaultClass \Drupal\sendwithus\Resolver\Template\TemplateResolver
 */
class TemplateResolverTest extends UnitTestCase {

  /**
   * @covers ::__construct
   * @covers ::addResolver
   * @covers ::resolve
   * @covers ::getResolvers
   */
  public function testDefault() {
    // Make sure we can't enter invalid resolvers.
    $exception = FALSE;
    try {
      new TemplateResolver([1]);
    }
    catch (\InvalidArgumentException $e) {
      $exception = TRUE;
    }
    $this->assertTrue($exception);

    $stub1 = $this->createMock(TemplateResolverInterface::class);
    $stub2 = $this->createMock(TemplateResolverInterface::class);

    $sut = new TemplateResolver();
    $sut->addResolver($stub1);
    $this->assertEquals(1, count($sut->getResolvers()));

    $sut = new TemplateResolver([$stub1, $stub2]);
    $this->assertEquals(2, count($sut->getResolvers()));

    $context = new Context('module', 'id', new ParameterBag(['data' => 123]));

    $this->assertEquals(NULL, $sut->resolve($context));

    $expected_template = new Template('1234');
    $stub2->method('resolve')
      ->willReturn($expected_template);

    $this->assertEquals($expected_template, $sut->resolve($context));

    // Make sure resolver with higher priority (added first) gets
    // processed first.
    $expected_template2 = new Template('12345');
    $stub1->method('resolve')
      ->willReturn($expected_template2);

    $this->assertEquals($expected_template2, $sut->resolve($context));
  }

}
