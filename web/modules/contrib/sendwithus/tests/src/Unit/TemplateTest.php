<?php

namespace Drupal\Tests\sendwithus\Unit;

use Drupal\sendwithus\Template;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Template unit tests.
 *
 * @group sendwithus
 * @coversDefaultClass \Drupal\sendwithus\Template
 */
class TemplateTest extends UnitTestCase {

  /**
   * @covers ::__construct
   * @covers ::getTemplateId
   * @covers ::setTemplateId
   * @covers ::setVariable
   * @covers ::setTemplateVariable
   * @covers ::getVariable
   * @covers ::getVariables
   * @covers ::toArray
   * @covers ::getIterator
   */
  public function testDefault() {
    $template = new Template('template_id');

    $this->assertEquals('template_id', $template->getTemplateId());
    $template->setTemplateId('new_id');
    $this->assertEquals('new_id', $template->getTemplateId());

    $data = [['address' => 'test@example.com']];
    $template->setVariable('bcc', $data);
    $this->assertEquals($data, $template->getVariable('bcc'));
    $template->setVariable('bcc', '123');
    $this->assertEquals('123', $template->getVariable('bcc'));

    $user = ['name' => 'Test', 'mail' => 'test@example.com'];
    $template->setTemplateVariable('user', $user);
    $this->assertEquals($user, $template->getVariable('template_data')['user']);
    $template->setTemplateVariable('test', '123');
    $this->assertEquals($user, $template->getVariable('template_data')['user']);

    $this->assertInstanceOf(ParameterBag::class, $template->getVariables());

    $expected = [
      'bcc' => '123',
      'template_data' => [
        'user' => $user,
        'test' => '123',
      ],
    ];
    $this->assertEquals($expected, $template->toArray());

    $i = 0;
    // Make sure we can iterate the template.
    foreach ($template as $key => $value) {
      $i++;
    }
    $this->assertEquals(2, $i);
  }

}
