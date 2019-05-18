<?php

namespace Drupal\Tests\sendwithus\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\sendwithus\Context;
use Drupal\sendwithus\Resolver\Variable\UserVariableCollector;
use Drupal\sendwithus\Template;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * UserVariableCollector kernel tests.
 *
 * @group sendwithus
 * @coversDefaultClass \Drupal\sendwithus\Resolver\Variable\UserVariableCollector
 */
class UserVariableCollectorTest extends KernelTestBase {

  use UserCreationTrait;

  public static $modules = ['sendwithus', 'key', 'user', 'system'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installSchema('system', 'sequences');
  }

  /**
   * @covers ::collect
   */
  public function testDefault() {
    $account = $this->createUser([], 'testaccount');
    $template = new Template('1234');
    $context = new Context('modulename', 'user_password_reset', new ParameterBag([
      'params' => ['account' => $account],
    ]));

    $sut = new UserVariableCollector();
    $sut->collect($template, $context);

    $data = $template->getVariable('template_data');
    $this->assertEquals('testaccount', $data['user']['name']);
    $this->assertEquals('testaccount', $data['user']['display_name']);
    $this->assertEquals('testaccount@example.com', $data['user']['mail']);
    $this->assertEquals('http://localhost/user/1/edit', $data['user']['edit_url']);
    $this->assertEquals('http://localhost/user/1/cancel', $data['user']['cancel_url']);

    // Make sure password reset link is not generated unless we specify
    // the 'password_reset' email key.
    $this->assertTrue(empty($data['user']['reset_url']));

    $context->getData()->set('key', 'password_reset');
    $sut->collect($template, $context);
    $data = $template->getVariable('template_data');

    $this->assertTrue(strpos($data['user']['reset_url'], 'http://localhost/user/reset/1/') !== FALSE);
  }

}
