<?php

namespace Drupal\Tests\gacsp\Unit\AnalyticsCommand\Linker;

use Drupal\gacsp\AnalyticsCommand\Linker\AutoLink;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\gacsp\AnalyticsCommand\Linker\AutoLink
 * @group gacsp
 */
class AutoLinkTest extends UnitTestCase {

  /**
   * Test the default priority.
   */
  public function testDefaultPriority() {
    $command = new AutoLink(['example.com']);

    $this->assertEquals(0, $command->getPriority());
  }

  /**
   * Test the command array for a basic command without additional options.
   */
  public function testBasicSettingCommands() {
    $command = new AutoLink(['one.example.com', 'two.example.com']);

    $this->assertEquals(
      [['linker:autoLink', ['one.example.com', 'two.example.com']]],
      $command->getSettingCommands()
    );
  }

  /**
   * Test the command when the useAnchor parameter is provided.
   */
  public function testWithAnchor() {
    $command = new AutoLink(['one.example.com', 'two.example.com'], TRUE);

    $this->assertEquals(
      [['linker:autoLink', ['one.example.com', 'two.example.com'], TRUE]],
      $command->getSettingCommands()
    );
  }

  /**
   * Test the command when useAnchor and decorateForms parameters are provided.
   */
  public function testWithAnchorAndForm() {
    $command = new AutoLink(['one.example.com', 'two.example.com'], TRUE, TRUE);

    $this->assertEquals(
      [['linker:autoLink', ['one.example.com', 'two.example.com'], TRUE, TRUE]],
      $command->getSettingCommands()
    );
  }

  /**
   * Test the command when only decorateForms parameter is provided.
   */
  public function testWithForm() {
    $command = new AutoLink(['one.example.com', 'two.example.com'], NULL, TRUE);

    $this->assertEquals(
      [['linker:autoLink', ['one.example.com', 'two.example.com'], FALSE, TRUE]],
      $command->getSettingCommands()
    );
  }

}
