<?php

namespace Drupal\Tests\sendwithus\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\sendwithus\Context;
use Drupal\sendwithus\Resolver\Variable\SystemVariableCollector;
use Drupal\sendwithus\Template;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * SystemVariableCollector kernel tests.
 *
 * @group sendwithus
 * @coversDefaultClass \Drupal\sendwithus\Resolver\Variable\SystemVariableCollector
 */
class SystemVariableCollectorTest extends KernelTestBase {

  public static $modules = ['sendwithus', 'key', 'system', 'user'];

  /**
   * @covers ::collect
   */
  public function testDefault() {
    $template = new Template('1234');
    $context = new Context('modulename', 'user_password_reset', new ParameterBag(['langcode' => 'fi']));

    $this->config('system.site')
      ->set('name', 'Site name')
      ->set('slogan', 'Site slogan')
      ->set('mail', 'admin@example.com')
      ->set('page', ['front' => '/'])
      ->save();

    $sut = new SystemVariableCollector($this->container->get('config.factory'), $this->container->get('url_generator'));
    $sut->collect($template, $context);

    $data = $template->getVariable('template_data');
    $expected = [
      'name' => 'Site name',
      'slogan' => 'Site slogan',
      'mail' => 'admin@example.com',
      'url' => 'http://localhost/',
      'login_url' => 'http://localhost/user',
    ];

    $this->assertEquals($expected, $data['site']);

    $expected = [
      'module' => 'modulename',
      'key' => 'user_password_reset',
      'body' => '',
      'subject' => '',
      'langcode' => 'fi',
    ];
    $this->assertEquals($expected, $data['mail']);
  }

}
