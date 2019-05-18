<?php

namespace Drupal\Tests\sendwithus\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\sendwithus\Context;
use Drupal\sendwithus\Entity\Template;
use Drupal\sendwithus\Resolver\Template\DefaultTemplateResolver;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * DefaultTemplatResolver kernel tests.
 *
 * @group sendwithus
 * @coversDefaultClass \Drupal\sendwithus\Resolver\Template\DefaultTemplateResolver
 */
class DefaultTemplateResolverTest extends KernelTestBase {

  public static $modules = ['sendwithus', 'key', 'system', 'user'];

  /**
   * @covers ::__construct
   * @covers ::resolve
   */
  public function testDefault() {
    $this->config('system.site')->set('page', ['front' => '/'])->save();

    $expected = [
      [
        'id' => 'template_id1',
        'key' => 'password_reset',
        'module' => 'user',
      ],
      [
        'id' => 'template_id2',
        'module' => 'user',
      ],
    ];

    foreach ($expected as $item) {
      Template::create($item)->save();
    }

    $sut = new DefaultTemplateResolver($this->container->get('sendwithus.variable.collector'), $this->container->get('entity_type.manager'));

    // Make sure null is returned when no valid module/key combination is given.
    $context = new Context('contact', 'contact_form', new ParameterBag([]));
    $this->assertEquals(NULL, $sut->resolve($context));

    // Make sure exact template is returned when both, module and key matches.
    $context = new Context('user', 'password_reset', new ParameterBag([]));
    $this->assertEquals('template_id1', $sut->resolve($context)->getTemplateId());

    // Make sure the last available template is returned when only
    // the module matches.
    $context = new Context('user', 'nonexistent', new ParameterBag([]));
    $this->assertEquals('template_id2', $sut->resolve($context)->getTemplateId());
  }

}
