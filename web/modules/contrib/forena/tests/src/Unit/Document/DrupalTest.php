<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 4/16/16
 * Time: 11:58 AM
 */

namespace Drupal\Tests\forena\Unit\Document;


use Drupal\Core\Ajax\InvokeCommand;
use Drupal\forena\DocManager;
use Drupal\Tests\forena\Unit\FrxTestCase;

/**
 * Test Drupal Document Features
 * @group Forena
 * @require module forena
 * @coversDefaultClass \Drupal\forena\FrxPlugin\Document\Drupal
 */
class DrupalTest extends FrxTestCase{


  public function testWrite() {
    DocManager::instance()->setDocument("drupal");
    $doc = DocManager::instance()->getDocument();
    $foo = 'hello';
    $doc->clear();
    $doc->write('hello ');
    $doc->write('world');
    $buffer1 = $doc->flush();
    $this->assertEquals('hello world', $buffer1['report']['#template']);
    $doc->clear();
    $doc->write('this ');
    $doc->write('day');
    $buffer2 = $doc->flush();
    $this->assertEquals('this day', $buffer2['report']['#template']);
    $this->assertFalse($buffer1['report']['#template'] == $buffer2['report']['#template']);
  }

  /**
   * Verify that we can add commands to the render array
   */
  public function testAddCommands() {
    $doc = DocManager::instance()->getDocument();
    $command = [
      'command' => 'invoke',
      'method' => 'attr',
      'selector' => 'input#my-id',
      'arguments' => ['checked', '1']
      ];
    $doc->addAjaxCommand($command, 'post');
    $commands = $doc->getAjaxCommands();
    $this->assertEquals(1, count($commands['post']));
    $this->assertInstanceOf('Drupal\Core\Ajax\InvokeCommand', $commands['post'][0]);

    
  }
}