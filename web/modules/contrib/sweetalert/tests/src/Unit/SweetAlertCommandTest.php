<?php

/**
 * @file
 * Contains \Drupal\Tests\sweetalert\Unit\SweetAlertCommandTest.
 */

namespace Drupal\Tests\sweetalert\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\Ajax\CommandInterface;
use Drupal\sweetalert\Ajax\SweetAlertCommand;

/**
 * @coversDefaultClass \Drupal\sweetalert\Ajax\SweetAlertCommand
 * @group igrowl
 */
class SweetAlertCommandTest extends UnitTestCase {

  /**
   * Assert that GrowlCommand is an instance of CommandInterface.
   */
  public function testSweetAlertCommandIsInstanceOfCommandInterface() {
    $command = new SweetAlertCommand();
    $this->assertTrue($command instanceof CommandInterface, 'SweetAlertCommand is not instance of CommandInterface.');
  }

  /**
   * Test that basic options passed in are correctly set on the SweetAlertCommand object.
   * @param $option
   * @param $value
   * @dataProvider sweetAlertCommandOptions
   */
  public function testSweetAlertCommandOptionsCanBeSet($option, $value) {
    $command = new SweetAlertCommand([$option => $value]);
    $command_options = $command->getOptions();
    $this->assertEquals($value, $command_options[$option]);
  }

  /**
   * Test that the render method returns a properly formatted array with our option set.
   */
  public function testSweetAlertCommandRenderArray() {
    $options = SweetAlertCommand::defaultOptions();
    $options['title'] = 'Test Alert Title';
    $options['text'] = 'Test alert message.';
    $options['type'] = 'info';

    $command = new SweetAlertCommand($options);

    $expected = array(
      'command' => 'sweetalert',
      'settings' => ['options' => $options],
    );

    $this->assertEquals($expected, $command->render());
  }

  /**
   * Data provider to test basic option overrides.
   * @return array
   */
  public function sweetAlertCommandOptions() {
    return [
      'title option' => ['title', 'Welcome!'],
      'text option' => ['text', 'Thank you for registering! Your new account is ready.'],
      'type option' => ['type', 'success'],
      'allowOutsideClick option' => ['allowOutsideClick', false],
      'showConfirmButton option' => ['showConfirmButton', true],
      'showCancelButton option' => ['showCancelButton', false],
      'closeOnConfirm option' => ['closeOnConfirm', true],
      'closeOnCancel option' => ['closeOnCancel', true],
      'confirmButtonText option' => ['confirmButtonText', 'Cool, lets go!'],
      'confirmButtonColor option' => ['confirmButtonColor', '#8CD4F5'],
      'cancelButtonText option' => ['cancelButtonText', 'Cancel'],
      'imageUrl option' => ['imageUrl', null],
      'imageSize option' => ['imageSize', null],
      'timer option' => ['timer', null],
      'customClass option' => ['customClass', ''],
      'html option' => ['html', false],
      'animation option' => ['animation', true],
      'allowEscapeKey option' => ['allowEscapeKey', true],
      'inputType option' => ['inputType', 'text'],
      'inputPlaceholder option' => ['inputPlaceholder', ''],
      'inputValue option' => ['inputValue', ''],
      'showLoaderOnConfirm option' => ['showLoaderOnConfirm', false]
    ];
  }
}