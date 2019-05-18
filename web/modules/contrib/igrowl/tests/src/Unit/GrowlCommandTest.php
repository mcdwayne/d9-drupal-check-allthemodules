<?php

/**
 * @file
 * Contains \Drupal\Tests\igrowl\Unit\GrowlCommandTest.
 */

namespace Drupal\Tests\igrowl\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\Ajax\CommandInterface;
use Drupal\igrowl\Ajax\GrowlCommand;

/**
 * @coversDefaultClass \Drupal\igrowl\Ajax\GrowlCommand
 * @group igrowl
 */
class GrowlCommandTest extends UnitTestCase {

  /**
   * Assert that GrowlCommand is an instance of CommandInterface.
   */
  public function testGrowlCommandIsInstanceOfCommandInterface() {
    $command = new GrowlCommand();
    $this->assertTrue($command instanceof CommandInterface, 'GrowlCommand is not instance of CommandInterface.');
  }

  /**
   * Test that basic options passed in are correctly set on the GrowlCommand object.
   * @param $option
   * @param $value
   * @dataProvider growlBasicOptions
   */
  public function testGrowlCommandOptionsCanBeSet($option, $value) {
    $command = new GrowlCommand([$option => $value]);
    $command_options = $command->getOptions();
    $this->assertEquals($value, $command_options[$option]);
  }

  /**
   * Test that the render method returns a properly formatted array with our option set.
   */
  public function testGrowlCommandRenderArray() {
    $options = GrowlCommand::defaultOptions();
    $options['title'] = 'Test Growl Title';
    $options['message'] = 'Test growl message.';

    $command = new GrowlCommand($options);

    $expected = array(
      'command' => 'igrowl',
      'settings' => ['options' => $options],
    );

    $this->assertEquals($expected, $command->render());
  }

  /**
   * Data provider to test basic option overrides.
   * @return array
   */
  public function growlBasicOptions() {
    return [
      'title option' => ['title', 'Test title.'],
      'type option' => ['type', 'success'],
      'message option' => ['message', 'This is a test for an iGrowl.'],
      'icon option' => ['icon', 'feather-check'],
      'small option' => ['small', true],
      'delay option' => ['delay', 1000],
      'spacing option' => ['spacing', 25],
      'animation option' => ['animation', false],
      'animShow option' => ['animShow', 'bounceOut'],
      'animHide option' => ['animHide', 'bounceIn'],
      'onShow option' => ['onShow', 'onShowTest'],
      'onShown option' => ['onShown', 'onShownTest'],
      'onHide option' => ['onHide', 'onHideTest'],
      'onHidden option' => ['onHidden', 'onHiddenTest'],
    ];
  }
}